<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerDashboardPollingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'Polling Business',
            'jenisbisnis' => 'Layanan',
            'slug' => 'polling-business',
            'alamat' => 'Jalan Test',
            'nomorhp' => '08123456789',
        ]);

        $this->service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Layanan Polling',
            'harga' => 100000,
            'durasi' => 60,
        ]);
    }

    public function test_owner_can_access_polling_endpoint_and_get_json()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->getJson(route('owner.dashboard.polling'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_bookings',
                    'total_revenue',
                    'recent_activities',
                ],
            ]);
    }

    public function test_revenue_uses_payment_status_sukses()
    {
        Payment::create([
            'idtenant' => $this->tenant->id,
            'idbooking' => null,
            'idlangganan' => null,
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 100000,
            'status' => 'pending',
        ]);

        Payment::create([
            'idtenant' => $this->tenant->id,
            'idbooking' => null,
            'idlangganan' => null,
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 50000,
            'status' => 'sukses',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->getJson(route('owner.dashboard.polling'));

        $response->assertStatus(200)
            ->assertJsonPath('data.total_revenue', 50000);
    }

    public function test_booking_status_paid_is_returned_and_dynamic()
    {
        $schedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => Carbon::today(),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        $booking = Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $schedule->id,
            'namapelanggan' => 'John Doe',
            'email' => 'john@example.com',
            'nomorhp' => '08123',
            'tanggalbooking' => Carbon::today(),
            'jam' => '10:00:00',
            'status' => 'pending',
        ]);

        $response1 = $this->actingAs($this->user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->getJson(route('owner.dashboard.polling'));

        $response1->assertJsonPath('data.recent_activities.0.status', 'pending');

        $booking->update(['status' => 'paid']);

        $response2 = $this->actingAs($this->user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->getJson(route('owner.dashboard.polling'));

        $response2->assertJsonPath('data.recent_activities.0.status', 'paid');
    }

    public function test_polling_does_not_mutate_database()
    {
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('payments', 0);

        $this->actingAs($this->user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->getJson(route('owner.dashboard.polling'));

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_other_tenant_data_is_not_read()
    {
        $otherUser = User::factory()->create(['role' => 'owner']);
        $otherTenant = Tenant::create([
            'iduser' => $otherUser->id,
            'namabisnis' => 'Other Business',
            'slug' => 'other-business',
            'jenisbisnis' => 'Layanan',
            'alamat' => 'Alamat',
            'nomorhp' => '08123',
        ]);
        
        $otherService = Service::create([
            'idtenant' => $otherTenant->id,
            'namalayanan' => 'Layanan',
            'harga' => 1000,
            'durasi' => 60,
        ]);

        $schedule = Schedule::create([
            'idtenant' => $otherTenant->id,
            'idlayanan' => $otherService->id,
            'tanggal' => Carbon::today(),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        Booking::create([
            'idtenant' => $otherTenant->id,
            'idlayanan' => $otherService->id,
            'idschedule' => $schedule->id,
            'namapelanggan' => 'Other Customer',
            'email' => 'cust@example.com',
            'nomorhp' => '08123',
            'tanggalbooking' => Carbon::today(),
            'jam' => '10:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->getJson(route('owner.dashboard.polling'));

        $response->assertJsonPath('data.total_bookings', 0);
    }
}
