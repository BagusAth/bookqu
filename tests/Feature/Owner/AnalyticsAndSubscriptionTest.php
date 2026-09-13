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
        $response->assertSee('Subscription');
    }

    public function test_owner_with_pro_trial_can_view_analytics(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/analytics');
        $response->assertStatus(200);
        $response->assertSee('Analytics');
    }

    public function test_owner_can_export_analytics_csv(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/analytics/export');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_owner_can_view_and_update_landing_page(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/landing-page');
        $response->assertStatus(200);

        $postResponse = $this->actingAs($this->user)->post('/owner/landing-page', [
            'custom_domain' => 'booking.example.com',
            'theme_color' => '#4F46E5',
        ]);

        $postResponse->assertRedirect();
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'custom_domain' => 'booking.example.com',
            'theme_color' => '#4F46E5',
        ]);
    }

    public function test_owner_without_pro_is_blocked_from_pro_features(): void
    {
        // Change to small plan and active (non-trial)
        Subscription::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->update([
            'idplan' => $this->starterPlan->id,
            'status' => 'active',
            'trial_berakhir' => null,
        ]);

        $response = $this->actingAs($this->user)->get('/owner/analytics');
        $response->assertRedirect('/owner/subscription');
        $response->assertSessionHas('error');
    }
}
