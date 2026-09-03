<?php

namespace Tests\Feature\Customer;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingManageTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Service $service;
    protected Schedule $schedule;
    protected Payment $payment;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create([
            'iduser' => $owner->id,
            'cancel_before_hours' => 24,
            'reschedule_before_hours' => 24,
        ]);

        $this->service = Service::factory()->create([
            'idtenant' => $this->tenant->id,
            'harga' => 150000,
            'durasi' => 60,
        ]);

        // Future schedule 3 days away (well within 24-hr policy)
        $this->schedule = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => Carbon::now()->addDays(3)->format('Y-m-d'),
            'jam_mulai' => '14:00:00',
            'jam_selesai' => '15:00:00',
            'status' => 'tersedia',
        ]);

        $this->payment = Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'jumlah' => 150000,
            'status' => 'sukses',
        ]);

        $this->booking = Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'idpayment' => $this->payment->id,
            'status' => 'paid',
            'tanggalbooking' => $this->schedule->tanggal,
            'jam' => $this->schedule->jam_mulai,
            'booking_code' => 'BKQ-TEST-12345',
            'cancellation_token' => 'valid-cancel-token-123',
            'reschedule_token' => 'valid-reschedule-token-123',
        ]);
    }

    public function test_customer_can_view_booking_with_valid_token(): void
    {
        $response = $this->get('/manage/' . $this->booking->booking_code . '?token=' . $this->booking->cancellation_token);
        $response->assertStatus(200);
        $response->assertSee($this->booking->booking_code);
    }

    public function test_customer_cannot_view_booking_with_invalid_token(): void
    {
        $response = $this->get('/manage/' . $this->booking->booking_code . '?token=wrong-token');
        $response->assertStatus(403);
    }

    public function test_customer_can_view_invoice(): void
    {
        $response = $this->get('/manage/' . $this->booking->booking_code . '/invoice?token=' . $this->booking->cancellation_token);
        $response->assertStatus(200);
        $response->assertSee($this->booking->booking_code);
    }

    public function test_customer_can_cancel_booking_within_policy(): void
    {
        $response = $this->post('/manage/' . $this->booking->booking_code . '/cancel?token=' . $this->booking->cancellation_token);
        $response->assertRedirect('/manage/' . $this->booking->booking_code . '?token=' . $this->booking->cancellation_token);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('refunds', [
            'booking_id' => $this->booking->id,
            'status' => 'pending',
            'jumlah' => 150000,
        ]);
    }

    public function test_customer_cannot_cancel_booking_outside_policy(): void
    {
        // Change booking to 2 hours from now (violates 24-hr policy)
        $this->booking->update([
            'tanggalbooking' => Carbon::today()->format('Y-m-d'),
            'jam' => Carbon::now()->addHours(2)->format('H:i:s'),
        ]);

        $response = $this->post('/manage/' . $this->booking->booking_code . '/cancel?token=' . $this->booking->cancellation_token);
        $response->assertSessionHasErrors('cancel');

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'paid',
        ]);
    }

    public function test_customer_can_view_reschedule_page_and_get_slots(): void
    {
        $response = $this->get('/manage/' . $this->booking->booking_code . '/reschedule?token=' . $this->booking->reschedule_token);
        $response->assertStatus(200);

        // New slot for tomorrow
        $newDate = Carbon::tomorrow()->format('Y-m-d');
        Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $newDate,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        $ajaxResponse = $this->getJson('/manage/' . $this->booking->booking_code . '/reschedule/slots?token=' . $this->booking->reschedule_token . '&tanggal=' . $newDate);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJsonStructure(['slots']);
    }

    public function test_customer_can_reschedule_to_new_slot(): void
    {
        $newSchedule = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'jam_mulai' => '16:00:00',
            'jam_selesai' => '17:00:00',
            'status' => 'tersedia',
        ]);

        $response = $this->post('/manage/' . $this->booking->booking_code . '/reschedule?token=' . $this->booking->reschedule_token, [
            'tanggal' => $newSchedule->tanggal,
            'schedule_id' => $newSchedule->id,
        ]);

        $response->assertRedirect('/manage/' . $this->booking->booking_code . '?token=' . $this->booking->reschedule_token);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'idschedule' => $newSchedule->id,
            'tanggalbooking' => $newSchedule->tanggal,
            'jam' => '16:00',
        ]);
    }
}
