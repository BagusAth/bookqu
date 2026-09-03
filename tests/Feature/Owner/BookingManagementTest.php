<?php

namespace Tests\Feature\Owner;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Service $service;
    protected Schedule $schedule;

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

        $this->service = Service::factory()->create([
            'idtenant' => $this->tenant->id,
        ]);

        $this->schedule = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
        ]);
    }

    public function test_owner_can_view_bookings_list(): void
    {
        Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'namapelanggan' => 'Alice Customer',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->user)->get('/owner/bookings');
        $response->assertStatus(200);
        $response->assertSee('Alice Customer');
    }

    public function test_owner_can_filter_bookings_by_status(): void
    {
        $schedule2 = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
        ]);

        Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'namapelanggan' => 'Pending Bob',
            'status' => 'pending',
        ]);

        Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $schedule2->id,
            'namapelanggan' => 'Paid Charlie',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->user)->get('/owner/bookings?status=paid');
        $response->assertStatus(200);
        $response->assertSee('Paid Charlie');
        $response->assertDontSee('Pending Bob');
    }

    public function test_owner_can_update_booking_status(): void
    {
        $booking = Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->user)->patch("/owner/bookings/{$booking->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed',
        ]);
    }
}
