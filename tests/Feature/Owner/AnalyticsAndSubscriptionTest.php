<?php

namespace Tests\Feature\Owner;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAndSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Plan $proPlan;

    protected Plan $starterPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::factory()->create([
            'iduser' => $this->user->id,
        ]);

        $this->proPlan = Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            [
                'hargabulanan' => 100000,
                'maxlayanan' => 10,
                'maxbooking' => 500,
                'isunlimited' => false,
            ]
        );

        $this->starterPlan = Plan::firstOrCreate(
            ['namapaket' => 'small'],
            [
                'hargabulanan' => 50000,
                'maxlayanan' => 3,
                'maxbooking' => 50,
                'isunlimited' => false,
            ]
        );

        // Active trial with Pro
        Subscription::create([
            'idtenant' => $this->tenant->id,
            'idplan' => $this->proPlan->id,
            'status' => 'trial',
            'trial_berakhir' => now()->addDays(7),
        ]);
    }

    public function test_owner_can_view_subscription_page(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/subscription');
        $response->assertStatus(200);
        // BQ-145: the page is titled per owner-portal.md §8.15.
        $response->assertSee('Paket & Tagihan');
    }

    public function test_owner_with_pro_trial_can_view_analytics(): void
    {
        // BQ-147: the page is Laporan (owner-portal.md §8.13), behind the `reports` module.
        config(['modules.reports' => 'released']);
        $response = $this->actingAs($this->user)->get('/owner/analytics');
        $response->assertStatus(200);
        $response->assertSee('Laporan');
    }

    public function test_owner_can_export_analytics_csv(): void
    {
        // BQ-147: Laporan is behind the `reports` module.
        config(['modules.reports' => 'released']);
        $response = $this->actingAs($this->user)->get('/owner/analytics/export');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_owner_can_view_and_update_landing_page(): void
    {
        // BQ-154: the landing builder is a Halaman Booking section; the theme colour moved to Tampilan (BQ-142).
        config(['modules.booking-page' => 'released']);

        $response = $this->actingAs($this->user)->get('/owner/landing-page');
        $response->assertRedirect(route('owner.booking-page', ['section' => 'landing']));
        $this->actingAs($this->user)->get(route('owner.booking-page', ['section' => 'domain']))->assertOk();

        $postResponse = $this->actingAs($this->user)->post(route('owner.booking-page.domain'), [
            'custom_domain' => 'booking.example.com',
        ]);

        $postResponse->assertRedirect();
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'custom_domain' => 'booking.example.com',
        ]);
    }

    public function test_owner_without_pro_is_blocked_from_pro_features(): void
    {
        // BQ-147: Laporan is behind the `reports` module.
        config(['modules.reports' => 'released']);
        // Change to small plan and active (non-trial)
        Subscription::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->update([
            'idplan' => $this->starterPlan->id,
            'status' => 'active',
            'trial_berakhir' => null,
        ]);

        // BQ-147: Small sees a preview without data; the export is refused (AC-LAP-03).
        $this->actingAs($this->user)->get('/owner/analytics')->assertOk()->assertSee('Laporan tersedia di paket Medium');
        $response = $this->actingAs($this->user)->get('/owner/analytics/export');
        $response->assertRedirect('/owner/subscription');
        $response->assertSessionHas('error');
    }
}
