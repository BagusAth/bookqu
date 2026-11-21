<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Integrations\ManageGoogleCalendar;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Jobs\DeliverGoogleCalendarEvent;
use App\Models\Booking;
use App\Models\OwnerIntegration;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class OwnerIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'modules.integrations' => 'released',
            'services.google_calendar.client_id' => 'test-client',
            'services.google_calendar.client_secret' => 'test-credential',
        ]);
        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        app(CreateTrialSubscription::class)->execute($this->tenant);
    }

    public function test_google_oauth_requires_its_session_state_and_keeps_tokens_private(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'test-access', 'refresh_token' => 'test-refresh', 'expires_in' => 3600,
        ]), 'https://oauth2.googleapis.com/revoke' => Http::response()]);

        $this->actingAs($this->owner);
        $callback = route('owner.settings.integrations.calendar.callback');
        $this->get($callback.'?state=foreign&code=test')->assertForbidden();
        Http::assertNothingSent();
        $response = $this->post(route('owner.settings.integrations.calendar.connect'))->assertRedirect();
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('S256', $query['code_challenge_method']);
        $url = $callback.'?'.http_build_query(['state' => $query['state'], 'code' => 'test-code']);
        $this->get($url)->assertRedirect(route('owner.settings', ['tab' => 'integrasi']))->assertSessionHasNoErrors();
        $this->get($url)->assertForbidden();

        $raw = DB::table('owner_integrations')->where('idtenant', $this->tenant->id)->value('google_refresh_token');
        $this->assertNotNull($raw);
        $this->assertNotSame('test-refresh', $raw);
        $this->get(route('owner.settings', ['tab' => 'integrasi']))->assertOk()->assertSee('Terhubung')
            ->assertDontSee('test-access')->assertDontSee('test-refresh');
        $this->delete(route('owner.settings.integrations.calendar.disconnect'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('owner_integrations', ['idtenant' => $this->tenant->id, 'google_refresh_token' => null]);
    }

    public function test_tracking_is_validated_and_only_rendered_for_the_connected_business(): void
    {
        $foreign = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($foreign);
        OwnerIntegration::factory()->create(['idtenant' => $foreign->id, 'google_analytics_id' => 'G-FOREIGN']);
        $route = route('owner.settings.integrations.tracking');

        $this->actingAs($this->owner)->put($route, ['idtenant' => $foreign->id, 'google_analytics_id' => 'G-LOCAL', 'meta_pixel_id' => '123456789'])
            ->assertSessionHasNoErrors();
        $this->put($route, ['google_analytics_id' => 'https://other.test', 'meta_pixel_id' => 'invalid'])
            ->assertSessionHasErrors(['google_analytics_id', 'meta_pixel_id']);
        $this->assertDatabaseHas('owner_integrations', ['idtenant' => $foreign->id, 'google_analytics_id' => 'G-FOREIGN']);
        $this->get('/'.$this->tenant->slug)->assertOk()->assertSee('G-LOCAL')->assertDontSee('G-FOREIGN');
    }

    public function test_calendar_delivery_failure_does_not_undo_the_booking(): void
    {
        Bus::fake([DeliverGoogleCalendarEvent::class]);
        Http::preventStrayRequests();
        Http::fake(['www.googleapis.com/*' => Http::sequence()
            ->push(['id' => 'test-event'], 201)->push(['error' => 'test-provider-error'], 500)]);
        OwnerIntegration::factory()->create(['idtenant' => $this->tenant->id, 'google_access_token' => 'test-access',
            'google_refresh_token' => 'test-refresh', 'google_expires_at' => now()->addHour()]);
        $service = Service::factory()->create(['idtenant' => $this->tenant->id]);
        $slot = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $service->id]);
        $booking = DB::transaction(fn () => Booking::factory()->create([
            'idtenant' => $this->tenant->id, 'idlayanan' => $service->id, 'idschedule' => $slot->id,
            'tanggalbooking' => $slot->tanggal, 'jam' => $slot->jam_mulai, 'status' => 'paid',
        ]));

        $job = Bus::dispatched(DeliverGoogleCalendarEvent::class)->sole();
        $this->assertSame($booking->id, $job->event->bookingId);
        Http::assertNothingSent();
        $job->handle(app(TenantContext::class), app(ManageGoogleCalendar::class));
        Http::assertSent(fn ($request): bool => $request->method() === 'POST' && filled($request['start']['dateTime'] ?? null));
        try {
            $job->handle(app(TenantContext::class), app(ManageGoogleCalendar::class));
            $this->fail('The worker must retry a rejected delivery.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Google Calendar refused the request.', $exception->getMessage());
        }
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'paid']);
    }
}
