<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Subscription\SubscriptionState;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LandingPagePricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_guest_sees_plan_registration_links_and_login_links_on_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Assert register URLs with plan parameters
        $response->assertSee(route('register', ['plan' => 'small']), false);
        $response->assertSee(route('register', ['plan' => 'medium']), false);
        $response->assertSee(route('register', ['plan' => 'pro']), false);

        // Assert button labels
        $response->assertSee('Mulai Sekarang');
        $response->assertSee('Pilih Medium');
        $response->assertSee('Pilih Pro');

        // Assert secondary login links
        $response->assertSee('Sudah punya akun? Masuk');
        $response->assertSee(route('login'));
    }

    public function test_registration_page_shows_selected_plan_banner_when_plan_parameter_present(): void
    {
        $response = $this->get(route('register', ['plan' => 'medium']));

        $response->assertStatus(200);
        $response->assertSee('Paket Terpilih:');
        $response->assertSee('Medium');
        $response->assertSee('Nikmati uji coba gratis 7 hari');
    }

    public function test_registration_page_does_not_show_banner_when_plan_parameter_absent(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertDontSee('Paket Terpilih:');
    }

    public function test_admin_user_sees_admin_dashboard_link_in_pricing_cards(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('admin.dashboard'));
        $response->assertSee('Buka Dashboard Admin');
        $response->assertDontSee('route(\'register\', [\'plan\' => \'pro\'])');
    }

    public function test_unverified_owner_sees_verification_link_in_pricing_cards(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($owner)->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('verification.notice'));
        $response->assertSee('Verifikasi Email Anda');
    }

    public function test_verified_owner_with_active_subscription_sees_active_package_and_upgrade_links(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $tenant = Tenant::factory()->create([
            'iduser' => $owner->id,
        ]);

        $mediumPlan = Plan::where('namapaket', 'medium')->firstOrFail();
        $proPlan = Plan::where('namapaket', 'pro')->firstOrFail();

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan' => $mediumPlan->id,
            'status' => SubscriptionState::STATUS_ACTIVE,
            'langganan_mulai' => now()->subDay(),
            'langganan_berakhir' => now()->addMonth(),
        ]);

        $response = $this->actingAs($owner)->get('/');

        $response->assertStatus(200);

        // Medium is the active plan
        $response->assertSee('✓ Paket Aktif Anda');
        $response->assertSee(route('owner.subscription'));

        // Pro is available for upgrade
        $response->assertSee('Upgrade ke Pro');
        $response->assertSee(route('owner.checkout', $proPlan->id));
    }

    public function test_owner_on_trial_sees_checkout_links_for_plans(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $tenant = Tenant::factory()->create([
            'iduser' => $owner->id,
        ]);

        $proPlan = Plan::where('namapaket', 'pro')->firstOrFail();
        $smallPlan = Plan::where('namapaket', 'small')->firstOrFail();
        $mediumPlan = Plan::where('namapaket', 'medium')->firstOrFail();

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan' => $proPlan->id,
            'status' => SubscriptionState::STATUS_TRIAL,
            'trial_berakhir' => now()->addDays(7),
        ]);

        $response = $this->actingAs($owner)->get('/');

        $response->assertStatus(200);

        // On trial, owner should be offered checkout options
        $response->assertSee(route('owner.checkout', $smallPlan->id));
        $response->assertSee(route('owner.checkout', $mediumPlan->id));
        $response->assertSee(route('owner.checkout', $proPlan->id));
    }

    public function test_owner_login_redirects_to_intended_checkout_url(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $proPlan = Plan::where('namapaket', 'pro')->firstOrFail();
        $intendedUrl = route('owner.checkout', $proPlan->id);

        $response = $this->withSession(['url.intended' => $intendedUrl])
            ->post('/login', [
                'email' => $owner->email,
                'password' => 'password123',
            ]);

        $this->assertAuthenticatedAs($owner);
        $response->assertRedirect($intendedUrl);
    }

    public function test_owner_on_pro_active_plan_sees_pilih_labels_for_lower_tiers(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $tenant = Tenant::factory()->create([
            'iduser' => $owner->id,
        ]);

        $smallPlan = Plan::where('namapaket', 'small')->firstOrFail();
        $mediumPlan = Plan::where('namapaket', 'medium')->firstOrFail();
        $proPlan = Plan::where('namapaket', 'pro')->firstOrFail();

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan' => $proPlan->id,
            'status' => SubscriptionState::STATUS_ACTIVE,
            'langganan_mulai' => now()->subDay(),
            'langganan_berakhir' => now()->addMonth(),
        ]);

        $response = $this->actingAs($owner)->get('/');

        $response->assertStatus(200);

        // Pro is the active plan
        $response->assertSee('✓ Paket Aktif Anda');

        // Lower tiers should NOT say "Upgrade ke Small"
        $response->assertDontSee('Upgrade ke Small');
        $response->assertDontSee('Upgrade ke Medium');
        $response->assertSee('Pilih Small');
        $response->assertSee('Pilih Medium');
        $response->assertSee(route('owner.checkout', $smallPlan->id));
        $response->assertSee(route('owner.checkout', $mediumPlan->id));
    }

    public function test_multi_tenant_owner_uses_session_current_tenant_context_for_pricing(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $tenant1 = Tenant::factory()->create(['iduser' => $owner->id, 'slug' => 'tenant-one']);
        $tenant2 = Tenant::factory()->create(['iduser' => $owner->id, 'slug' => 'tenant-two']);

        $smallPlan = Plan::where('namapaket', 'small')->firstOrFail();
        $proPlan = Plan::where('namapaket', 'pro')->firstOrFail();

        // Tenant 1 has Small plan active
        Subscription::create([
            'idtenant' => $tenant1->id,
            'idplan' => $smallPlan->id,
            'status' => SubscriptionState::STATUS_ACTIVE,
            'langganan_mulai' => now()->subDay(),
            'langganan_berakhir' => now()->addMonth(),
        ]);

        // Tenant 2 has Pro plan active
        Subscription::create([
            'idtenant' => $tenant2->id,
            'idplan' => $proPlan->id,
            'status' => SubscriptionState::STATUS_ACTIVE,
            'langganan_mulai' => now()->subDay(),
            'langganan_berakhir' => now()->addMonth(),
        ]);

        // When Tenant 2 is in session, pricing shows Pro as active and Small as lower tier
        $response = $this->actingAs($owner)
            ->withSession(['current_tenant_id' => $tenant2->id])
            ->get('/');

        $response->assertStatus(200);
        $response->assertSee('✓ Paket Aktif Anda');
        $response->assertSee('Pilih Small');
    }

    public function test_authenticated_customer_sees_logout_and_no_secondary_login_link(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($customer)->get('/');

        $response->assertStatus(200);

        // Authenticated customer should not see "Sudah punya akun? Masuk"
        $response->assertDontSee('Sudah punya akun? Masuk');

        // Customer should see logout form, not login link
        $response->assertSee(route('logout'));
    }

    public function test_owner_with_expired_subscription_sees_checkout_links(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $tenant = Tenant::factory()->create([
            'iduser' => $owner->id,
        ]);

        $proPlan = Plan::where('namapaket', 'pro')->firstOrFail();

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan' => $proPlan->id,
            'status' => SubscriptionState::STATUS_EXPIRED,
            'langganan_mulai' => now()->subMonths(2),
            'langganan_berakhir' => now()->subMonth(),
        ]);

        $response = $this->actingAs($owner)->get('/');

        $response->assertStatus(200);

        // Expired subscription should NOT show "✓ Paket Aktif Anda"
        $response->assertDontSee('✓ Paket Aktif Anda');
        $response->assertSee(route('owner.checkout', $proPlan->id));
    }

    public function test_mobile_segmented_tab_switcher_and_navbar_touch_target_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Assert mobile segmented switcher tabs exist
        $response->assertSee('set-active-pricing');
        $response->assertSee('activeMobileTab');

        // Assert hamburger touch target minimum sizing class
        $response->assertSee('min-h-[44px]');
        $response->assertSee('min-w-[44px]');
    }
}
