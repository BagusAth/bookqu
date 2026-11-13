<?php

namespace Tests\Feature\Customer;

use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesCustomerAccess;
use Tests\TestCase;

class BookingManageTest extends TestCase
{
    use RefreshDatabase;
    use UsesCustomerAccess;

    protected Tenant $tenant;

    protected Service $service;

    protected Schedule $schedule;

    protected Payment $payment;

    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        $this->withoutMiddleware([
            PreventRequestForgery::class,
            ValidateCsrfToken::class,
        ]);
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
        $response = $this->grantCustomerAccess($this->booking)->get('/manage/'.$this->booking->booking_code.'?token='.$this->booking->cancellation_token);
        $response->assertStatus(200);
        $response->assertSee($this->booking->booking_code);
    }

    public function test_customer_cannot_view_booking_with_invalid_token(): void
    {
        $response = $this->get('/manage/'.$this->booking->booking_code.'?token=wrong-token');
        $response->assertNotFound();
    }

    public function test_customer_can_view_invoice(): void
    {
        $response = $this->grantCustomerAccess($this->booking)->get('/manage/'.$this->booking->booking_code.'/invoice?token='.$this->booking->cancellation_token);
        $response->assertStatus(200);
        $response->assertSee($this->booking->booking_code);
    }

    public function test_management_pages_only_offer_refund_when_policy_allows_it(): void
    {
        $single = route('booking.manage', $this->booking->booking_code);
        $group = route('booking.manage.payment', $this->payment->order_id);
        $this->grantCustomerAccess($this->payment)->get($single)->assertOk()
            ->assertSeeText('Hubungi pemilik')->assertDontSee('name="nomor_rekening"', false);

        $this->tenant->update(['refunds_enabled' => true]);
        foreach ([$single, $group] as $url) {
            $this->get($url)->assertOk()->assertSee('name="nomor_rekening"', false)
                ->assertSee(route('booking.manage.cancel', $this->booking->booking_code))
                ->assertSeeText('Booking tetap berlaku sampai pemilik menyetujui pengajuan Anda.');
        }
    }

    public function test_customer_requests_refund_then_owner_approves_half_and_admin_processes_it(): void
    {
        $payload = [
            'reason' => 'Berhalangan hadir', 'bank_nama' => 'Bank Tes',
            'nomor_rekening' => '1234567890', 'nama_pemilik_rekening' => 'Pelanggan Tes',
        ];
        $url = '/manage/'.$this->booking->booking_code.'/cancel';
        $this->grantCustomerAccess($this->booking)->post($url, $payload)->assertSessionHasErrors('cancel');
        $this->booking->tenant->update(['refunds_enabled' => true]);
        $this->booking->payment->update(['jumlah' => 150001]);
        $this->grantCustomerAccess($this->booking)->post($url, $payload)->assertSessionHas('success');
        $this->post($url, $payload)->assertSessionHasErrors('cancel');
        $refundRequest = DB::table('refund_requests')->first();
        $this->assertNotSame($payload['nomor_rekening'], $refundRequest->nomor_rekening);
        $this->assertSame('paid', $this->booking->fresh()->status);
        $this->assertDatabaseCount('refunds', 0);

        $decision = '/owner/refunds/requests/'.$refundRequest->id;
        $this->actingAs(Tenant::factory()->create()->user)->patch($decision, ['decision' => 'accepted_half'])->assertNotFound();
        $this->actingAs($this->booking->tenant->user)->patch($decision, ['decision' => 'accepted_half'])->assertSessionHas('sukses');
        $this->patch($decision, ['decision' => 'accepted_half'])->assertRedirect();
        $this->assertDatabaseCount('refunds', 1);
        $this->assertDatabaseHas('refunds', ['refund_request_id' => $refundRequest->id, 'jumlah' => 75000, 'cause' => 'customer_request']);
        $this->assertSame('cancelled', $this->booking->fresh()->status);
        $refund = Refund::firstOrFail();
        $this->get('/admin/refunds')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/refunds')
            ->assertOk()->assertDontSee($payload['nomor_rekening']);
        $this->post("/admin/refunds/{$refund->id}/transfer")->assertOk()->assertSee($payload['nomor_rekening']);
        $this->patch("/admin/refunds/{$refund->id}", ['status' => 'processed', 'reference' => 'TEST-TRANSFER'])->assertRedirect();
        $this->assertDatabaseHas('refunds', ['id' => $refund->id, 'status' => 'processed', 'refund_reference' => 'TEST-TRANSFER']);
    }

    public function test_customer_cannot_cancel_booking_outside_policy(): void
    {
        $this->booking->tenant->update(['refunds_enabled' => true]);
        // Change booking to 2 hours from now (violates 24-hr policy)
        $this->booking->update([
            'tanggalbooking' => Carbon::today()->format('Y-m-d'),
            'jam' => Carbon::now()->addHours(2)->format('H:i:s'),
        ]);

        $response = $this->grantCustomerAccess($this->booking)->post('/manage/'.$this->booking->booking_code.'/cancel?token='.$this->booking->cancellation_token);
        $response->assertSessionHasErrors('cancel');

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'paid',
        ]);
    }

    public function test_customer_can_view_reschedule_page_and_get_slots(): void
    {
        $response = $this->grantCustomerAccess($this->booking)->get('/manage/'.$this->booking->booking_code.'/reschedule?token='.$this->booking->reschedule_token);
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

        $ajaxResponse = $this->getJson('/manage/'.$this->booking->booking_code.'/reschedule/slots?token='.$this->booking->reschedule_token.'&tanggal='.$newDate);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJsonStructure(['slots']);
    }

    public function test_reschedule_page_does_not_offer_a_date_closed_by_the_owner(): void
    {
        $closedDate = Carbon::tomorrow()->addDay()->format('Y-m-d');
        $openDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');
        foreach ([$closedDate, $openDate] as $date) {
            Schedule::factory()->create([
                'idtenant' => $this->tenant->id,
                'idlayanan' => $this->service->id,
                'tanggal' => $date,
                'jam_mulai' => '10:00:00',
                'jam_selesai' => '11:00:00',
                'status' => 'tersedia',
            ]);
        }
        OwnerBlockedDate::create(['idtenant' => $this->tenant->id, 'tanggal' => $closedDate, 'alasan' => 'Libur']);

        $response = $this->grantCustomerAccess($this->booking)->get('/manage/'.$this->booking->booking_code.'/reschedule?token='.$this->booking->reschedule_token);

        $response->assertOk();
        $offered = collect($response->viewData('availabilityPayload'))->pluck('date')->all();
        $this->assertContains($openDate, $offered);
        $this->assertNotContains($closedDate, $offered);
    }

    public function test_reschedule_onto_a_date_closed_by_the_owner_is_refused(): void
    {
        $closedDate = Carbon::tomorrow()->addDays(3)->format('Y-m-d');
        $slot = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $closedDate,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);
        OwnerBlockedDate::create(['idtenant' => $this->tenant->id, 'tanggal' => $closedDate, 'alasan' => 'Libur']);
        $originalSchedule = $this->booking->idschedule;

        $response = $this->grantCustomerAccess($this->booking)->post('/manage/'.$this->booking->booking_code.'/reschedule?token='.$this->booking->reschedule_token, [
            'tanggal' => $closedDate,
            'schedule_id' => $slot->id,
        ]);

        $response->assertSessionHasErrors('reschedule');
        $this->assertSame($originalSchedule, $this->booking->fresh()->idschedule);
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

        $response = $this->grantCustomerAccess($this->booking)->post('/manage/'.$this->booking->booking_code.'/reschedule?token='.$this->booking->reschedule_token, [
            'tanggal' => $newSchedule->tanggal,
            'schedule_id' => $newSchedule->id,
        ]);

        $response->assertRedirect('/manage/'.$this->booking->booking_code);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'idschedule' => $newSchedule->id,
            'tanggalbooking' => $newSchedule->tanggal,
            'jam' => '16:00',
        ]);

        // Verify owner received the reschedule notification
        $owner = $this->tenant->user;
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'notifiable_type' => get_class($owner),
        ]);

        $notification = $owner->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('rescheduled', $notification->data['event_type']);
        $this->assertEquals('Jadwal Booking Diubah', $notification->data['title']);
    }

    public function test_invoice_keeps_the_recorded_charge_after_catalog_and_override_changes(): void
    {
        $this->service->update(['harga' => 250000]);
        $this->schedule->update(['harga_override' => 275000]);
        $response = $this->grantCustomerAccess($this->booking)->get('/manage/'.$this->booking->booking_code.'/invoice');
        $response->assertOk()->assertSee('Rp 150.000')->assertDontSee('275.000')->assertDontSee('250.000');
        $this->assertEquals(150000, $this->payment->fresh()->jumlah);
        $this->assertSame('sukses', $this->payment->fresh()->status);
        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_group_invoice_does_not_invent_legacy_per_slot_prices(): void
    {
        $schedule = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'harga_override' => 900000]);
        Booking::factory()->create([
            'idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id,
            'idschedule' => $schedule->id, 'idpayment' => $this->payment->id, 'status' => 'paid',
        ]);
        $this->payment->update(['jumlah' => 350000]);
        $this->service->update(['harga' => 800000]);
        $this->grantCustomerAccess($this->payment)->get('/manage/payment/'.$this->payment->order_id.'/invoice')
            ->assertOk()->assertSee('350.000')->assertSee('Rincian harga historis per sesi tidak tersedia.')
            ->assertDontSee('800.000')->assertDontSee('900.000')->assertDontSee('175.000');
        $this->assertEquals(350000, $this->payment->fresh()->jumlah);
        $this->assertDatabaseCount('refunds', 0);
    }
}
