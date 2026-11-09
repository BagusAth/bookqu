<?php

namespace Tests\Feature\Owner;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerSidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'My Booking Business',
            'jenisbisnis' => 'Klinik',
            'slug' => 'my-business',
            'alamat' => 'Jalan Test',
            'nomorhp' => '08123456789',
            'deskripsi' => 'Testing business',
        ]);

        $plan = Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            ['hargabulanan' => 499000, 'maxlayanan' => 0, 'maxbooking' => 0, 'isunlimited' => true]
        );
        Subscription::create([
            'idtenant' => $this->tenant->id,
            'idplan' => $plan->id,
            'status' => 'active',
        ]);
    }

    public function test_owner_can_access_all_expanded_sidebar_pages(): void
    {
        $routes = [
            '/owner/dashboard',
            '/owner/calendar',
            '/owner/schedule',
            '/owner/bookings',
            '/owner/categories',
            '/owner/services',
            '/owner/reviews',
            '/owner/customers',
            '/owner/settings/business',
            '/owner/subscription',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->user)->get($route);
            $response->assertStatus(200);
            $response->assertSee('BookQu');
            // BQ-128 (BQ-091): the owner app no longer calls itself "Admin Portal".
            $response->assertDontSee('Admin Portal');
            $response->assertSee('Beranda')->assertSee('Layanan')->assertSee('Jam Buka &amp; Harga', false);
            $response->assertDontSee('id="nav-integrations"', false);

            // Hidden features must NOT be rendered in the sidebar
            $response->assertDontSee('id="nav-landing-page"', false);
            $response->assertDontSee('id="nav-staff-resources"', false);
            $response->assertDontSee('id="nav-additional-items"', false);
            $response->assertDontSee('id="nav-vouchers"', false);
            $response->assertDontSee('id="nav-appearance"', false);
            $response->assertDontSee('id="nav-payments"', false);
            $response->assertDontSee('id="nav-assets"', false);
        }
    }

    public function test_owner_settings_hides_business_logo_and_payment_configuration(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/settings');
        $response->assertStatus(200);

        // Logo upload and payment configuration sections must be hidden
        $response->assertDontSee('id="input-logo"', false);
        $response->assertDontSee('Business Logo');
        $response->assertDontSee('id="payment-settings"', false);
        $response->assertDontSee('id="payout-settings"', false);

        // Standard profile fields remain accessible
        $response->assertSee('id="input-namabisnis"', false);
        $response->assertSee('id="input-jenisbisnis"', false);
        $response->assertSee('id="input-alamat"', false);
        $response->assertSee('id="input-nomorhp"', false);
    }

    public function test_owner_dashboard_hides_payment_verification_prompt_and_links_to_locked_analytics(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/dashboard');
        $response->assertStatus(200);

        // Payment prompt banner must NOT be visible
        $response->assertDontSee('id="payment-verification-banner"', false);
        $response->assertDontSee('Pengaturan pembayaran belum diverifikasi');

        // Full report link has locked indicator
        $response->assertDontSee('id="link-full-report"', false);
        $this->get('/owner/analytics')->assertNotFound();
        $this->get('/owner/schedule-report')->assertNotFound();
        $this->get('/owner/settings/integrations')->assertNotFound();
    }

    public function test_unbuilt_redesign_report_routes_are_hidden_instead_of_showing_development_teasers(): void
    {
        $this->actingAs($this->user)->get('/owner/analytics')->assertNotFound();
        $this->get('/owner/schedule-report')->assertNotFound();
    }

    public function test_owner_topbar_dropdown_hides_appearance_and_payment_links(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/dashboard');
        $response->assertStatus(200);

        // Must not contain links to hidden features in dropdown
        $response->assertDontSee(route('owner.settings.appearance'));
        $response->assertDontSee(route('owner.settings.payment-setting'));

        // Retains active links: Pengaturan and Paket & Tagihan come from the navigation definition (BQ-128)
        $response->assertSee(route('owner.settings'));
        $response->assertSee(route('owner.subscription'));
    }
}
