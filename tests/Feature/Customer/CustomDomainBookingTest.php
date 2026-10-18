<?php

namespace Tests\Feature\Customer;

use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
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
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
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

    public function test_customer_can_select_program_via_custom_domain(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'studiobrama.com'])
            ->post('http://studiobrama.com/booking/select-program', [
                'service_id' => $this->service->id,
            ]);

        $response->assertRedirect('http://studiobrama.com/booking/date');
        $this->assertEquals($this->service->id, session('booking.service_id'));
    }

    public function test_unknown_custom_domain_returns_404(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'unknown-tenant.com'])
            ->get('http://unknown-tenant.com/');

        $response->assertStatus(404);
    }
}
