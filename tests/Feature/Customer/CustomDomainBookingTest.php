<?php

namespace Tests\Feature\Customer;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\Tenant\CustomDomain;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomDomainBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Service $service;

    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00', 'Asia/Jakarta'));
        $this->withoutMiddleware([
            PreventRequestForgery::class,
            ValidateCsrfToken::class,
        ]);

        $this->user = User::factory()->create(['role' => 'owner']);
        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'Studio Brama',
            'jenisbisnis' => 'Studio Foto',
            'slug' => 'studio-brama',
            'custom_domain' => 'studiobrama.com',
            'alamat' => 'Jalan Sunset No. 8',
            'nomorhp' => '08123456789',
        ]);
        // BQ-154: a domain serves its business only after its DNS check connected it.
        $this->tenant->forceFill(['custom_domain_status' => 'connected', 'custom_domain_verified_at' => now()])->save();

        $this->service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Photoshoot Personal',
            'harga' => 350000,
            'durasi' => 60,
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);
    }

    public function test_customer_can_access_catalog_via_custom_domain(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'studiobrama.com'])
            ->get('http://studiobrama.com/');

        $response->assertStatus(200);
        $response->assertSee('Studio Brama');
        $response->assertSee('Photoshoot Personal');
    }

    public function test_platform_hosts_keep_the_landing_page_and_generate_slug_storefront_urls(): void
    {
        app(CreateTrialSubscription::class)->execute($this->tenant);

        foreach (['bookqu.my.id', 'preview.bookqu.test'] as $host) {
            $this->get('http://'.$host.'/')->assertOk()->assertDontSee('Studio Brama');
            $this->get('http://'.$host.'/studio-brama')->assertOk()->assertSee('Studio Brama')
                ->assertSee('action="http://'.$host.'/studio-brama/booking/select-program"', false);
        }
    }

    public function test_platform_domain_matching_uses_a_label_boundary_for_custom_domains(): void
    {
        $host = 'bookqu.test.studiobrama.com';
        $this->tenant->update(['custom_domain' => $host]);
        app(CreateTrialSubscription::class)->execute($this->tenant);

        $this->get('http://'.$host.'/')->assertOk()->assertSee('Studio Brama')
            ->assertSee('action="http://'.$host.'/booking/select-program"', false);
    }

    public function test_the_configured_app_host_is_reserved_for_platform_routing_and_domain_registration(): void
    {
        config(['app.url' => 'https://booking.example:8443']);
        $pattern = '~^'.CustomDomain::tenantHostPattern().'$~iD';

        foreach (['booking.example', 'preview.booking.example'] as $host) {
            $this->assertTrue(CustomDomain::isPlatformHost($host));
            $this->assertFalse(CustomDomain::isAllowed($host));
            $this->assertDoesNotMatchRegularExpression($pattern, $host);
        }
        $this->assertMatchesRegularExpression($pattern, 'booking.example.studiobrama.com');
    }

    public function test_customer_can_select_program_via_custom_domain(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'studiobrama.com'])
            ->post('http://studiobrama.com/booking/select-program', [
                'service_id' => $this->service->id,
            ]);

        $response->assertRedirect('http://studiobrama.com/booking/schedule');
        $this->assertEquals($this->service->id, session('booking.service_id'));
    }

    public function test_booking_forms_and_navigation_keep_the_custom_domain_through_checkout(): void
    {
        // BQ-139: successful creation requires an active period; original behavior assertions remain.
        app(CreateTrialSubscription::class)->execute($this->tenant);
        $this->withServerVariables(['HTTP_HOST' => 'studiobrama.com'])->withSession(['booking' => [
            'tenant_id' => $this->tenant->id, 'service_id' => $this->service->id,
            'tanggal' => $this->schedule->tanggal->toDateString(), 'jam' => ['10:00'],
            'schedule_ids' => [$this->schedule->id],
        ]]);

        foreach ([
            '/' => '/booking/select-program',
            '/booking/schedule' => '/booking/select-schedule',
            '/booking/checkout' => '/booking/checkout',
        ] as $page => $action) {
            $this->get('http://studiobrama.com'.$page)->assertOk()
                ->assertSee('action="http://studiobrama.com'.$action.'"', false)
                ->assertSee('href="http://studiobrama.com"', false)
                ->assertDontSee('http://studio-brama/', false);
        }
    }

    public function test_a_saved_domain_that_is_not_connected_returns_404(): void
    {
        // BQ-154: pending or failing DNS never resolves to the business (INV-TEN-01).
        foreach (['pending', 'not_connected'] as $status) {
            $this->tenant->forceFill(['custom_domain_status' => $status])->save();

            $this->withServerVariables(['HTTP_HOST' => 'studiobrama.com'])->get('http://studiobrama.com/')->assertNotFound();
        }
    }

    public function test_unknown_custom_domain_returns_404(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'unknown-tenant.com'])
            ->get('http://unknown-tenant.com/');

        $response->assertStatus(404);
    }
}
