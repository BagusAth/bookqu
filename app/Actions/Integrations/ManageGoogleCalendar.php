<?php

declare(strict_types=1);

namespace App\Actions\Integrations;

use App\Domain\Integrations\BookingIntegrationEvent;
use App\Domain\Integrations\CalendarIntegrationFailed;
use App\Domain\Modules\ModuleRelease;
use App\Infrastructure\Integrations\GoogleCalendar;
use App\Models\OwnerIntegration;
use App\Models\Tenant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

final class ManageGoogleCalendar
{
    private const STATE_KEY = 'google_calendar_oauth';

    public function __construct(private GoogleCalendar $calendar) {}

    public function begin(Tenant $tenant, int $userId, Session $session): string
    {
        $this->requireConfiguration();
        $state = bin2hex(random_bytes(32));
        $verifier = bin2hex(random_bytes(48));
        $session->put(self::STATE_KEY, [
            'state' => $state, 'verifier' => $verifier, 'tenant' => $tenant->id, 'user' => $userId,
            'expires' => now()->addMinutes(10)->getTimestamp(),
        ]);

        return $this->calendar->authorizationUrl($state, $verifier);
    }

    public function connect(Tenant $tenant, int $userId, Session $session, string $state, string $code): void
    {
        $this->requireConfiguration();
        $attempt = $session->pull(self::STATE_KEY);
        abort_unless(is_array($attempt) && $attempt['tenant'] === $tenant->id && $attempt['user'] === $userId
            && $attempt['expires'] >= now()->getTimestamp() && hash_equals($attempt['state'], $state), 403);
        if ($code === '' || strlen($code) > 2048) {
            throw ValidationException::withMessages(['calendar' => 'Izin Google Calendar belum diberikan.']);
        }

        $this->locked($tenant, function () use ($tenant, $code, $attempt): void {
            $settings = OwnerIntegration::firstOrNew(['idtenant' => $tenant->id]);
            if ($settings->google_refresh_token !== null) {
                throw new CalendarIntegrationFailed('Disconnect the current calendar first.');
            }
            // Reserve the unique settings row before exchanging a single-use authorization code.
            if (! $settings->exists) {
                $settings = OwnerIntegration::firstOrCreate(['idtenant' => $tenant->id]);
            }
            $this->calendar->connect($settings, $code, $attempt['verifier']);
        });
    }

    public function updateConnection(Tenant $tenant, bool $disconnect): void
    {
        $this->locked($tenant, function () use ($tenant, $disconnect): void {
            $settings = OwnerIntegration::where('idtenant', $tenant->id)->first();
            if ($settings === null || $settings->google_refresh_token === null) {
                throw new CalendarIntegrationFailed('No Google Calendar is connected.');
            }
            if ($disconnect) {
                $this->calendar->disconnect($settings);
            } else {
                $this->calendar->test($settings);
            }
        });
    }

    public function deliver(Tenant $tenant, BookingIntegrationEvent $event): void
    {
        $this->locked($tenant, function () use ($tenant, $event): void {
            $settings = OwnerIntegration::where('idtenant', $tenant->id)->first();
            if ($settings?->google_refresh_token !== null && $this->calendar->configured()) {
                $this->calendar->deliver($settings, $event);
            }
        });
    }

    /** @param callable(): void $operation */
    private function locked(Tenant $tenant, callable $operation): void
    {
        Cache::lock('google-calendar:tenant:'.$tenant->id, 40)->block(4, $operation);
    }

    private function requireConfiguration(): void
    {
        abort_unless(ModuleRelease::state('integrations') === 'released', 404);
        if (! $this->calendar->configured()) {
            throw ValidationException::withMessages(['calendar' => 'Google Calendar belum dikonfigurasi.']);
        }
    }
}
