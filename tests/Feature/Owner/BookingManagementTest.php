<?php

namespace Tests\Feature\Owner;

use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
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
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));

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

    public function test_owner_can_cancel_multi_slot_booking_group(): void
    {
        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'tipe' => 'booking',
            'jumlah' => 150000,
            'status' => 'sukses',
            'metode' => 'midtrans',
            'order_id' => 'BKG-OWNER-CANCEL-GRP',
            'manage_token' => Booking::generateSecureToken(),
            'nama_pembayar' => 'Multi Slot Customer',
            'email_pembayar' => 'customer@example.com',
            'hp_pembayar' => '081234567890',
        ]);

        $schedule2 = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => now()->addDays(2)->toDateString(),
        ]);

        $b1 = Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Multi Slot Customer',
            'nomorhp' => '081234567890',
            'email' => 'customer@example.com',
            'tanggalbooking' => $this->schedule->tanggal,
            'jam' => $this->schedule->jam_mulai,
            'status' => 'paid',
            'booking_code' => 'BK-MULTI-OWNER-1',
            'cancellation_token' => Booking::generateSecureToken(),
            'reschedule_token' => Booking::generateSecureToken(),
        ]);

        $b2 = Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $schedule2->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Multi Slot Customer',
            'nomorhp' => '081234567890',
            'email' => 'customer@example.com',
            'tanggalbooking' => $schedule2->tanggal,
            'jam' => $schedule2->jam_mulai,
            'status' => 'paid',
            'booking_code' => 'BK-MULTI-OWNER-2',
            'cancellation_token' => Booking::generateSecureToken(),
            'reschedule_token' => Booking::generateSecureToken(),
        ]);

        $this->assertTrue($b1->isMultiSlot());

        // Owner cancels one slot -> both slots should be cancelled
        $response = $this->actingAs($this->user)->patch("/owner/bookings/{$b1->id}/status", [
            'status' => 'cancelled',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('sukses');

        $b1->refresh();
        $b2->refresh();
        $this->assertSame('cancelled', $b1->status);
        $this->assertSame('cancelled', $b2->status);
    }

    public function test_owner_walk_in_without_email_is_recorded_without_a_management_delivery(): void
    {
        $walkinSchedule = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => now()->addDays(3)->toDateString(),
            'jam_mulai' => '18:00',
            'jam_selesai' => '19:00',
            'status' => 'tersedia',
        ]);

        $this->actingAs($this->user)->post('/owner/bookings/walkin', [
            'idschedule' => $walkinSchedule->id,
            'namapelanggan' => 'Walkin Tanpa Email',
            'nomorhp' => '089988776600',
            'metode' => 'cash',
        ])->assertRedirect()->assertSessionHas('sukses');

        $booking = Booking::withoutGlobalScopes()->where('namapelanggan', 'Walkin Tanpa Email')->firstOrFail();
        $this->assertNull($booking->email);
        $this->assertSame('paid', $booking->status);
        $this->assertDatabaseMissing('customer_access_deliveries', ['payment_id' => $booking->idpayment]);
    }

    public function test_owner_walk_in_booking_creates_payment_with_management_delivery_and_provider(): void
    {
        $walkinSchedule = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => now()->addDays(3)->toDateString(),
            'jam_mulai' => '14:00',
            'jam_selesai' => '15:00',
            'status' => 'tersedia',
        ]);

        $response = $this->actingAs($this->user)->post('/owner/bookings/walkin', [
            'idschedule' => $walkinSchedule->id,
            'namapelanggan' => 'Walkin Guest',
            'nomorhp' => '089988776655',
            'email' => 'walkin@example.com',
            'metode' => 'cash',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('sukses');

        $booking = Booking::withoutGlobalScopes()->where('namapelanggan', 'Walkin Guest')->first();
        $this->assertNotNull($booking);
        $this->assertNotNull($booking->payment);
        $this->assertNull($booking->payment->manage_token);
        $this->assertSame('manual', $booking->payment->provider);
        $this->assertDatabaseHas('customer_access_deliveries', ['payment_id' => $booking->idpayment]);
    }

    public function test_owner_bookings_page_does_not_expose_customer_credentials(): void
    {
        $booking = Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'namapelanggan' => 'Secure Token Test',
            'nomorhp' => '081234567890',
            'email' => 'token@example.com',
            'tanggalbooking' => $this->schedule->tanggal,
            'jam' => $this->schedule->jam_mulai,
            'status' => 'paid',
            'booking_code' => 'BK-SECURE-TOKEN',
            'cancellation_token' => 'my-secret-cancel-token',
            'reschedule_token' => 'my-secret-reschedule-token',
        ]);

        $response = $this->actingAs($this->user)->get('/owner/bookings');
        $response->assertStatus(200);
        $response->assertDontSee('my-secret-cancel-token');
        $response->assertDontSee('my-secret-reschedule-token');
    }

    public function test_owner_walk_in_booking_blocked_when_subscription_expired(): void
    {
        $plan = Plan::firstOrCreate(
            ['namapaket' => 'small'],
            ['hargabulanan' => 50000, 'maxlayanan' => 5, 'maxbooking' => 50, 'isunlimited' => false]
        );

        Subscription::create([
            'idtenant' => $this->tenant->id,
            'idplan' => $plan->id,
            'status' => 'expired',
            'langganan_berakhir' => now()->subDay(),
        ]);

        $walkinSchedule = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => now()->addDays(3)->toDateString(),
            'jam_mulai' => '16:00',
            'jam_selesai' => '17:00',
            'status' => 'tersedia',
        ]);

        $response = $this->actingAs($this->user)->post('/owner/bookings/walkin', [
            'idschedule' => $walkinSchedule->id,
            'namapelanggan' => 'Walkin Blocked Guest',
            'nomorhp' => '089988776611',
            'email' => 'blocked@example.com',
            'metode' => 'cash',
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseMissing('bookings', [
            'namapelanggan' => 'Walkin Blocked Guest',
        ]);
    }

    public function test_reschedule_rejects_a_slot_from_another_selected_date_without_mutation(): void
    {
        $booking = Booking::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'idschedule' => $this->schedule->id, 'status' => 'paid']);
        $target = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => '2026-11-05', 'jam_mulai' => '10:00:00', 'jam_selesai' => '11:00:00']);
        $this->actingAs($this->user)->postJson('/owner/bookings/'.$booking->id.'/reschedule', ['tanggal' => '2026-11-06', 'schedule_id' => $target->id])->assertUnprocessable();
        $this->assertSame($this->schedule->id, $booking->fresh()->idschedule);
    }

    public function test_reschedule_accepts_a_slot_matching_the_selected_date(): void
    {
        $booking = Booking::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'idschedule' => $this->schedule->id, 'status' => 'paid']);
        $target = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => '2026-11-05', 'jam_mulai' => '10:00:00', 'jam_selesai' => '11:00:00']);
        $this->actingAs($this->user)->postJson('/owner/bookings/'.$booking->id.'/reschedule', ['tanggal' => '2026-11-05', 'schedule_id' => $target->id])->assertOk()->assertJsonPath('success', true);
        $this->assertSame($target->id, $booking->fresh()->idschedule);
        $this->assertSame('2026-11-05', $booking->fresh()->tanggalbooking->toDateString());
    }

    public function test_closed_date_slots_are_unselectable_and_reopening_restores_bookability(): void
    {
        $booking = Booking::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'idschedule' => $this->schedule->id, 'status' => 'paid']);
        $target = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => '2026-11-05', 'jam_mulai' => '10:00:00', 'jam_selesai' => '11:00:00']);
        $closed = OwnerBlockedDate::create(['idtenant' => $this->tenant->id, 'tanggal' => '2026-11-05', 'alasan' => 'Libur']);
        $this->actingAs($this->user)->getJson('/owner/bookings/'.$booking->id.'/available-slots?tanggal=2026-11-05')
            ->assertOk()->assertJsonPath('slots.0.is_available', false);
        $this->postJson('/owner/bookings/'.$booking->id.'/reschedule', ['tanggal' => '2026-11-05', 'schedule_id' => $target->id])->assertUnprocessable();
        $this->assertSame($this->schedule->id, $booking->fresh()->idschedule);
        $closed->delete();
        $this->getJson('/owner/bookings/'.$booking->id.'/available-slots?tanggal=2026-11-05')
            ->assertOk()->assertJsonPath('slots.0.is_available', true);
        $this->postJson('/owner/bookings/'.$booking->id.'/reschedule', ['tanggal' => '2026-11-05', 'schedule_id' => $target->id])->assertOk();
        $this->assertSame($target->id, $booking->fresh()->idschedule);
    }
}
