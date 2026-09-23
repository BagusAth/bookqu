<?php

namespace Tests\Feature\Owner;

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

        $plan = \App\Models\Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            ['hargabulanan' => 499000, 'maxlayanan' => 0, 'maxbooking' => 0, 'isunlimited' => true]
        );
        \App\Models\Subscription::create([
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
            '/owner/schedule-report',
            '/owner/categories',
            '/owner/services',
            '/owner/reviews',
            '/owner/customers',
            '/owner/settings/business',
            '/owner/settings/integrations',
            '/owner/subscription',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->user)->get($route);
            $response->assertStatus(200);
            $response->assertSee('BookQu');
            $response->assertSee('Admin Portal');
            $response->assertSee('BOOKING');
            $response->assertSee('BUSINESS');
            $response->assertSee('CUSTOMERS');
            $response->assertSee('MARKETING');
            $response->assertSee('ANALYTICS');
            $response->assertSee('SETTINGS');
            $response->assertSee('Services');
            $response->assertSee('Bookings');
            $response->assertSee('Terkunci');

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
        $response->assertSee('id="link-full-report"', false);
        $response->assertSee('Terkunci');
    }

    public function test_owner_analytics_and_schedule_report_render_locked_state(): void
    {
        $resAnalytics = $this->actingAs($this->user)->get('/owner/analytics');
        $resAnalytics->assertStatus(200);
        $resAnalytics->assertSee('Fitur Sedang Dikembangkan');
        $resAnalytics->assertSee('Modul Analitik &amp; Laporan Bisnis', false);

        $resSchedule = $this->actingAs($this->user)->get('/owner/schedule-report');
        $resSchedule->assertStatus(200);
        $resSchedule->assertSee('Fitur Sedang Dikembangkan');
        $resSchedule->assertSee('Laporan Okupansi &amp; Utilisasi Jadwal', false);
    }

    public function test_owner_topbar_dropdown_hides_appearance_and_payment_links(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/dashboard');
        $response->assertStatus(200);

        // Must not contain links to hidden features in dropdown
        $response->assertDontSee(route('owner.settings.appearance'));
        $response->assertDontSee(route('owner.settings.payment-setting'));

        // Retains active links
        $response->assertSee(route('owner.settings.business'));
        $response->assertSee(route('owner.subscription'));
    }
}
