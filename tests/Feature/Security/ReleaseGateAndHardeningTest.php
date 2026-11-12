<?php

namespace Tests\Feature\Security;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * BQ-006 (subscription fail closed), BQ-007 (auth throttling), BQ-008 (staged module gate),
 * BQ-010 (test-token command), BQ-011 (reserved tenant slugs).
 */
class ReleaseGateAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'saldo_platform' => 500000]);
    }

    public function test_hidden_module_answers_not_found_for_reads_and_writes(): void
    {
        $this->actingAs($this->owner)->get('/owner/vouchers')->assertNotFound();
        $this->actingAs($this->owner)->post('/owner/vouchers', ['code' => 'X'])->assertNotFound();
        $this->actingAs($this->owner)->post('/owner/settings/payment', ['payment_mode' => 'owner'])->assertNotFound();
        $this->assertDatabaseCount('vouchers', 0);
    }

    public function test_locked_module_serves_reads_but_refuses_writes(): void
    {
        // BQ-130: isolate this behavior behind an explicitly enabled development module.
        config(['modules.integrations' => 'locked']);
        Route::middleware(['web', 'module:integrations'])->match(['get', 'post'], '/_test/locked-module', fn () => 'ok');

        $this->get('/_test/locked-module')->assertOk();
        $this->post('/_test/locked-module')->assertForbidden();
    }

    public function test_released_module_is_served(): void
    {
        // BQ-130: isolate this behavior behind an explicitly enabled development module.
        app(CreateTrialSubscription::class)->execute($this->tenant);
        config(['modules.vouchers' => 'released']);

        $this->actingAs($this->owner)->get('/owner/vouchers')->assertOk();
    }

    public function test_module_missing_from_registry_is_hidden(): void
    {
        Route::middleware(['web', 'module:not-registered'])->get('/_test/unregistered-module', fn () => 'ok');

        $this->get('/_test/unregistered-module')->assertNotFound();
    }

    public function test_every_staged_route_carries_its_module_gate(): void
    {
        $staged = [
            'owner.vouchers' => 'vouchers',
            'owner.additional-items' => 'additional-items',
            'owner.team' => 'team',
            'owner.resources' => 'staff-resources',
            // BQ-142: the former appearance and assets pages redirect inside Halaman Booking.
            'owner.settings.appearance' => 'booking-page',
            'owner.booking-page' => 'booking-page',
            'owner.settings.payment' => 'direct-payment',
            'owner.settings.assets' => 'booking-page',
            'owner.landing-page' => 'landing-page',
            'owner.settings.integrations' => 'integrations',
            // BQ-147: Laporan and the former schedule report sit behind the redesign module `reports`.
            'owner.analytics' => 'reports',
            'owner.schedule-report' => 'reports',
            'owner.settings.balance' => 'balance',
            'owner.payouts' => 'balance',
        ];

        $checked = 0;
        foreach (Route::getRoutes()->getRoutes() as $route) {
            /** @var RoutingRoute $route */
            $name = (string) $route->getName();
            foreach ($staged as $prefix => $module) {
                if ($name === $prefix || str_starts_with($name, $prefix.'.') || str_starts_with($name, $prefix.'-')) {
                    $this->assertContains('module:'.$module, $route->gatherMiddleware(), "Route {$name} lacks module:{$module}");
                    $checked++;
                }
            }
        }

        $this->assertGreaterThanOrEqual(30, $checked);
    }

    public function test_subscription_gate_refuses_a_request_without_tenant(): void
    {
        Route::middleware(['web', 'auth', 'subscription'])->get('/_test/subscription-gated', fn () => 'ok');
        $userWithoutTenant = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);

        $this->actingAs($userWithoutTenant)->get('/_test/subscription-gated')->assertForbidden();
    }

    public function test_login_attempts_are_throttled(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->post('/login', ['email' => $this->owner->email, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => $this->owner->email, 'password' => 'wrong-password'])->assertTooManyRequests();
    }

    public function test_test_token_command_refuses_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('booking:test-token')->assertFailed();
    }

    public function test_registration_never_takes_a_slug_shadowed_by_a_route(): void
    {
        $this->post('/register', [
            'name' => 'Owner Manage',
            'email' => 'manage@example.com',
            'nomorhp' => '08123456789',
            'nama_bisnis' => 'Manage',
            'jenis_bisnis' => 'Kesehatan',
            'alamat' => 'Jl. Sudirman No. 1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => 'on',
        ]);

        $tenant = Tenant::whereHas('user', fn ($query) => $query->where('email', 'manage@example.com'))->firstOrFail();
        $this->assertSame('manage-1', $tenant->slug);
    }
}
