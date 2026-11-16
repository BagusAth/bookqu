<?php

declare(strict_types=1);

namespace App\Infrastructure\Integrations;

use App\Actions\Booking\GetBookingTimeRanges;
use App\Contracts\Integrations\OwnerIntegration;
use App\Domain\Booking\BookingState;
use App\Domain\Integrations\BookingIntegrationEvent;
use App\Domain\Integrations\CalendarIntegrationFailed;
use App\Models\Booking;
use App\Models\OwnerIntegration as Settings;
use App\Models\Service;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class GoogleCalendar implements OwnerIntegration
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

    public function configured(): bool
    {
        return filled(config('services.google_calendar.client_id')) && filled(config('services.google_calendar.client_secret'));
    }

    public function authorizationUrl(string $state, string $verifier): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google_calendar.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events.owned',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
            'code_challenge_method' => 'S256',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function connect(Settings $settings, string $code, string $verifier): void
    {
        $this->storeTokens($settings, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'code_verifier' => $verifier,
            'redirect_uri' => $this->redirectUri(),
        ], true);
    }

    public function test(Settings $settings): void
    {
        $this->ensureSuccess($this->send(fn () => $this->calendar($settings)->get(self::EVENTS_URL, ['maxResults' => 1])));
    }

    public function disconnect(Settings $settings): void
    {
        if ($settings->google_refresh_token !== null) {
            $response = $this->send(fn () => $this->http()->asForm()->post('https://oauth2.googleapis.com/revoke', ['token' => $settings->google_refresh_token]));
            if (! $response->successful() && $response->json('error') !== 'invalid_token') {
                throw new CalendarIntegrationFailed('Google Calendar revocation failed.');
            }
        }

        $settings->update(['google_access_token' => null, 'google_refresh_token' => null, 'google_expires_at' => null]);
    }

    public function deliver(Settings $settings, BookingIntegrationEvent $event): void
    {
        $booking = Booking::where('idtenant', $event->tenantId)->with('layanan')->find($event->bookingId);
        if ($booking === null || $booking->status === BookingState::STATUS_PENDING) {
            return;
        }

        $id = 'bq'.hash('sha256', $this->redirectUri().':'.$event->tenantId.':'.$event->bookingId);
        $url = self::EVENTS_URL.'/'.$id;
        if ($booking->status === BookingState::STATUS_CANCELLED) {
            $response = $this->send(fn () => $this->calendar($settings)->delete($url));
            if (! in_array($response->status(), [404, 410], true)) {
                $this->ensureSuccess($response);
            }

            return;
        }

        $ranges = app(GetBookingTimeRanges::class)->execute([$booking]);
        $range = $ranges['sessionRanges'][$booking->id];
        if ($range->end === null) {
            return;
        }

        $service = $booking->layanan;
        $serviceName = $service instanceof Service ? $service->namalayanan : 'Booking';
        $body = [
            'id' => $id,
            'summary' => $serviceName.' · '.($booking->booking_code ?? $booking->id),
            'start' => ['dateTime' => $range->start->toRfc3339String()],
            'end' => ['dateTime' => $range->end->toRfc3339String()],
        ];
        $response = $this->send(fn () => $this->calendar($settings)->post(self::EVENTS_URL, $body));
        if ($response->status() === 409) {
            $response = $this->send(fn () => $this->calendar($settings)->put($url, $body));
        }
        $this->ensureSuccess($response);
    }

    private function calendar(Settings $settings): PendingRequest
    {
        if ($settings->google_access_token === null || $settings->google_expires_at?->lte(now()->addMinute())) {
            $this->storeTokens($settings, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $settings->google_refresh_token,
            ], false);
        }

        return $this->http()->withToken((string) $settings->google_access_token);
    }

    /** @param array<string, mixed> $grant */
    private function storeTokens(Settings $settings, array $grant, bool $requireRefreshToken): void
    {
        $response = $this->send(fn () => $this->http()->asForm()->post(self::TOKEN_URL, $grant + [
            'client_id' => config('services.google_calendar.client_id'),
            'client_secret' => config('services.google_calendar.client_secret'),
        ]));
        $this->ensureSuccess($response);
        $access = $response->json('access_token');
        $refresh = $response->json('refresh_token');
        $expires = $response->json('expires_in');
        if (! is_string($access) || $access === '' || ! is_numeric($expires) || (int) $expires <= 0
            || ($requireRefreshToken && (! is_string($refresh) || $refresh === ''))) {
            throw new CalendarIntegrationFailed('Google Calendar returned an incomplete grant.');
        }

        $settings->forceFill([
            'google_access_token' => $access,
            'google_refresh_token' => is_string($refresh) && $refresh !== '' ? $refresh : $settings->google_refresh_token,
            'google_expires_at' => now()->addSeconds((int) $expires),
        ])->save();
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->withoutRedirecting()->connectTimeout(3)->timeout(6);
    }

    /** @param callable(): Response $request */
    private function send(callable $request): Response
    {
        try {
            return $request();
        } catch (ConnectionException) {
            throw new CalendarIntegrationFailed('Google Calendar could not be reached.');
        }
    }

    private function ensureSuccess(Response $response): void
    {
        if (! $response->successful()) {
            throw new CalendarIntegrationFailed('Google Calendar refused the request.');
        }
    }

    private function redirectUri(): string
    {
        return rtrim((string) config('app.url'), '/').route('owner.settings.integrations.calendar.callback', absolute: false);
    }
}
