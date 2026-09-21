<?php

namespace Tests\Feature\Customer;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MidtransPaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductionLogicSpecificationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Service $service;
    protected string $date;
    protected Schedule $slot10;
    protected Schedule $slot11;
    protected Schedule $slot12;
    protected Schedule $slot14;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $user = User::factory()->create(['role' => 'owner']);
        $this->tenant = Tenant::create([
            'iduser' => $user->id,
            'namabisnis' => 'Studio Foto Logic',
            'jenisbisnis' => 'Studio Foto',
            'slug' => 'studio-foto-logic',
            'alamat' => 'Jl. Sudirman No. 10',
            'nomorhp' => '081234567890',
            'cancel_before_hours' => 24,
            'reschedule_before_hours' => 24,
        ]);

        $this->service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Sesi Foto 1 Jam',
            'harga' => 150000,
            'durasi' => 60,
            'satuan_durasi' => 'menit',
            'is_active' => true,
        ]);

        $this->date = Carbon::tomorrow('Asia/Jakarta')->toDateString();

        // Contiguous 60-min slots: 10:00-11:00, 11:00-12:00, 12:00-13:00
        $this->slot10 = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->date,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        $this->slot11 = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->date,
            'jam_mulai' => '11:00:00',
            'jam_selesai' => '12:00:00',
            'status' => 'tersedia',
        ]);

        $this->slot12 = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->date,
            'jam_mulai' => '12:00:00',
            'jam_selesai' => '13:00:00',
            'status' => 'tersedia',
        ]);

        // Non-contiguous slot: 14:00-15:00 (gap from 12:00/13:00)
        $this->slot14 = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->date,
            'jam_mulai' => '14:00:00',
            'jam_selesai' => '15:00:00',
            'status' => 'tersedia',
        ]);
    }

    /**
     * Helper to populate session booking context
     */
    protected function setBookingSession(array $scheduleIds, array $times): void
    {
        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->date,
                'schedule_id' => $scheduleIds[0] ?? null,
                'schedule_ids' => $scheduleIds,
                'jam' => $times,
                'times' => $times,
            ]
        ]);
    }

    /**
     * 1. Single slot checkout succeeds
     */
    public function test_1_single_slot_checkout_succeeds(): void
    {
        $this->setBookingSession([$this->slot10->id], ['10:00']);

        $response = $this->post('/studio-foto-logic/booking/checkout', [
            'namapelanggan' => 'Andi',
            'email' => 'andi@example.com',
            'nomorhp' => '081234567891',
        ]);

        $payment = Payment::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals(150000, (int) $payment->jumlah);

        $bookings = Booking::withoutGlobalScopes()->where('idpayment', $payment->id)->get();
        $this->assertCount(1, $bookings);
        $this->assertEquals('pending', $bookings->first()->status);
        $this->assertEquals('10:00:00', substr($bookings->first()->jam, 0, 8) . (strlen($bookings->first()->jam) === 5 ? ':00' : ''));

        $response->assertRedirect('/studio-foto-logic/booking/payment/' . $payment->order_id);
    }

    /**
     * 2. Two contiguous slots succeed
     */
    public function test_2_two_contiguous_slots_succeed(): void
    {
        $this->setBookingSession([$this->slot10->id, $this->slot11->id], ['10:00', '11:00']);

        $response = $this->post('/studio-foto-logic/booking/checkout', [
            'namapelanggan' => 'Budi',
            'email' => 'budi@example.com',
            'nomorhp' => '081234567892',
        ]);

        $payment = Payment::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(300000, (int) $payment->jumlah);

        $bookings = Booking::withoutGlobalScopes()->where('idpayment', $payment->id)->get();
        $this->assertCount(2, $bookings);
        $this->assertEquals(['pending', 'pending'], $bookings->pluck('status')->all());

        $response->assertRedirect('/studio-foto-logic/booking/payment/' . $payment->order_id);
    }

    /**
     * 3. Three contiguous slots succeed
     */
    public function test_3_three_contiguous_slots_succeed(): void
    {
        $this->setBookingSession([$this->slot10->id, $this->slot11->id, $this->slot12->id], ['10:00', '11:00', '12:00']);

        $response = $this->post('/studio-foto-logic/booking/checkout', [
            'namapelanggan' => 'Cici',
            'email' => 'cici@example.com',
            'nomorhp' => '081234567893',
        ]);

        $payment = Payment::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(450000, (int) $payment->jumlah);

        $bookings = Booking::withoutGlobalScopes()->where('idpayment', $payment->id)->get();
        $this->assertCount(3, $bookings);

        $response->assertRedirect('/studio-foto-logic/booking/payment/' . $payment->order_id);
    }

    /**
     * 4. Non-contiguous slots rejected
     */
    public function test_4_non_contiguous_slots_rejected(): void
    {
        // 10:00 and 14:00 have a gap
        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->date,
            ]
        ]);

        $response = $this->post('/studio-foto-logic/booking/select-time', [
            'jam' => ['10:00', '14:00'],
            'schedule_ids' => [$this->slot10->id, $this->slot14->id],
        ]);

        $response->assertSessionHasErrors(['jam']);
        $this->assertNotEquals(['10:00', '14:00'], session('booking.jam'));
    }

    /**
     * 5. Duplicate schedule rejected
     */
    public function test_5_duplicate_schedule_rejected(): void
    {
        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->date,
            ]
        ]);

        $response = $this->post('/studio-foto-logic/booking/select-time', [
            'jam' => ['10:00', '10:00'],
            'schedule_ids' => [$this->slot10->id, $this->slot10->id],
        ]);

        $response->assertSessionHasErrors(['jam.0']);
    }

    /**
     * 6. Schedule from another tenant rejected
     */
    public function test_6_schedule_from_another_tenant_rejected(): void
    {
        $otherUser = User::factory()->create(['role' => 'owner']);
        $otherTenant = Tenant::create([
            'iduser' => $otherUser->id,
            'namabisnis' => 'Other Tenant',
            'jenisbisnis' => 'Studio',
            'slug' => 'other-tenant',
            'alamat' => 'Jl. Lain',
            'nomorhp' => '08999999999',
        ]);
        $otherService = Service::create([
            'idtenant' => $otherTenant->id,
            'namalayanan' => 'Other Service',
            'harga' => 100000,
            'durasi' => 60,
            'is_active' => true,
        ]);
        $otherSchedule = Schedule::create([
            'idtenant' => $otherTenant->id,
            'idlayanan' => $otherService->id,
            'tanggal' => $this->date,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->date,
            ]
        ]);

        $response = $this->post('/studio-foto-logic/booking/select-time', [
            'jam' => ['10:00'],
            'schedule_ids' => [$otherSchedule->id],
        ]);

        $response->assertSessionHasErrors(['jam']);
    }

    /**
     * 7. Schedule from another service rejected
     */
    public function test_7_schedule_from_another_service_rejected(): void
    {
        $serviceB = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Service B',
            'harga' => 200000,
            'durasi' => 60,
            'is_active' => true,
        ]);
        $scheduleB = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $serviceB->id,
            'tanggal' => $this->date,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->date,
            ]
        ]);

        $response = $this->post('/studio-foto-logic/booking/select-time', [
            'jam' => ['10:00'],
            'schedule_ids' => [$scheduleB->id],
        ]);

        $response->assertSessionHasErrors(['jam']);
    }

    /**
     * 8. Schedule from another date rejected
     */
    public function test_8_schedule_from_another_date_rejected(): void
    {
        $differentDate = Carbon::parse($this->date)->addDays(5)->toDateString();
        $scheduleDiffDate = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $differentDate,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->date,
            ]
        ]);

        $response = $this->post('/studio-foto-logic/booking/select-time', [
            'jam' => ['10:00'],
            'schedule_ids' => [$scheduleDiffDate->id],
        ]);

        $response->assertSessionHasErrors(['jam']);
    }

    /**
     * 9. Concurrent booking cannot double-book slot
     */
    public function test_9_concurrent_booking_cannot_double_book_slot(): void
    {
        // Mark slot10 as already booked
        Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'namapelanggan' => 'Existing Customer',
            'email' => 'existing@example.com',
            'nomorhp' => '081111111111',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
        ]);

        $this->setBookingSession([$this->slot10->id], ['10:00']);

        $response = $this->post('/studio-foto-logic/booking/checkout', [
            'namapelanggan' => 'Late Customer',
            'email' => 'late@example.com',
            'nomorhp' => '082222222222',
        ]);

        $response->assertSessionHasErrors(['jam']);
        $this->assertDatabaseMissing('payments', ['namapelanggan' => 'Late Customer']);
    }

    /**
     * 10. One payment creates multiple bookings
     */
    public function test_10_one_payment_creates_multiple_bookings(): void
    {
        $this->setBookingSession([$this->slot10->id, $this->slot11->id], ['10:00', '11:00']);

        $response = $this->post('/studio-foto-logic/booking/checkout', [
            'namapelanggan' => 'Dedi',
            'email' => 'dedi@example.com',
            'nomorhp' => '081234567894',
        ]);

        $payment = Payment::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->first();
        $this->assertNotNull($payment);

        $bookings = $payment->bookings()->withoutGlobalScopes()->get();
        $this->assertEquals(2, $bookings->count());
        $this->assertEquals([$this->slot10->id, $this->slot11->id], $bookings->pluck('idschedule')->all());
    }

    /**
     * 11. Successful payment marks ALL bookings paid
     */
    public function test_11_successful_payment_marks_all_bookings_paid(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-MULTI-SUCCESS',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 300000,
            'status' => 'pending',
            'snap_token' => 'mock-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        $b1 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Eko',
            'email' => 'eko@example.com',
            'nomorhp' => '081234567895',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'pending',
        ]);

        $b2 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Eko',
            'email' => 'eko@example.com',
            'nomorhp' => '081234567895',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'pending',
        ]);

        /** @var MidtransPaymentService $midtransService */
        $midtransService = app(MidtransPaymentService::class);
        $midtransService->processSuccess($payment);

        $payment->refresh();
        $this->assertEquals('sukses', $payment->status);

        $b1->refresh();
        $b2->refresh();
        $this->assertEquals('paid', $b1->status);
        $this->assertEquals('paid', $b2->status);
        $this->assertNotEmpty($b1->booking_code);
        $this->assertNotEmpty($b2->booking_code);
        $this->assertNotEquals($b1->booking_code, $b2->booking_code);
    }

    /**
     * 12. Failed payment cancels ALL bookings
     */
    public function test_12_failed_payment_cancels_all_bookings(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-MULTI-FAIL',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 300000,
            'status' => 'pending',
            'snap_token' => 'mock-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        $b1 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Fajar',
            'email' => 'fajar@example.com',
            'nomorhp' => '081234567896',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'pending',
        ]);

        $b2 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Fajar',
            'email' => 'fajar@example.com',
            'nomorhp' => '081234567896',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'pending',
        ]);

        /** @var MidtransPaymentService $midtransService */
        $midtransService = app(MidtransPaymentService::class);
        $midtransService->processFailed($payment);

        $payment->refresh();
        $this->assertEquals('gagal', $payment->status);

        $b1->refresh();
        $b2->refresh();
        $this->assertEquals('cancelled', $b1->status);
        $this->assertEquals('cancelled', $b2->status);
    }

    /**
     * 13. Expired payment cancels ALL bookings
     */
    public function test_13_expired_payment_cancels_all_bookings(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-MULTI-EXPIRED',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 300000,
            'status' => 'pending',
            'snap_token' => 'mock-token',
            'expired_at' => now()->subMinutes(5),
        ]);

        $b1 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Gani',
            'email' => 'gani@example.com',
            'nomorhp' => '081234567897',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'pending',
        ]);

        $b2 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Gani',
            'email' => 'gani@example.com',
            'nomorhp' => '081234567897',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'pending',
        ]);

        Artisan::call('bookings:expire-payments');

        $payment->refresh();
        $this->assertEquals('gagal', $payment->status);

        $b1->refresh();
        $b2->refresh();
        $this->assertEquals('cancelled', $b1->status);
        $this->assertEquals('cancelled', $b2->status);
    }

    /**
     * 14. Cancel payment cancels ALL bookings
     */
    public function test_14_cancel_payment_cancels_all_bookings(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-CANCEL-ALL',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 300000,
            'status' => 'pending',
            'snap_token' => 'mock-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        $b1 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Hadi',
            'email' => 'hadi@example.com',
            'nomorhp' => '081234567898',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'pending',
        ]);

        $b2 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Hadi',
            'email' => 'hadi@example.com',
            'nomorhp' => '081234567898',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'pending',
        ]);

        $response = $this->post('/studio-foto-logic/booking/payment/' . $payment->order_id . '/cancel');
        $response->assertRedirect('/studio-foto-logic');

        $payment->refresh();
        $this->assertEquals('gagal', $payment->status);

        $b1->refresh();
        $b2->refresh();
        $this->assertEquals('cancelled', $b1->status);
        $this->assertEquals('cancelled', $b2->status);
    }

    /**
     * 15. Invoice displays ALL booking slots
     */
    public function test_15_invoice_displays_all_booking_slots(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-INVOICE-MULTI',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 300000,
            'status' => 'sukses',
            'snap_token' => 'mock-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'booking_code' => 'BK-SLOT10-001',
            'namapelanggan' => 'Indra',
            'email' => 'indra@example.com',
            'nomorhp' => '081234567899',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
        ]);

        Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'booking_code' => 'BK-SLOT11-002',
            'namapelanggan' => 'Indra',
            'email' => 'indra@example.com',
            'nomorhp' => '081234567899',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'paid',
        ]);

        $response = $this->get('/studio-foto-logic/booking/payment/' . $payment->order_id . '/invoice');
        $response->assertStatus(200);
        $response->assertSee('BK-SLOT10-001');
        $response->assertSee('BK-SLOT11-002');
        $response->assertSee('10:00 WIB');
        $response->assertSee('11:00 WIB');
    }

    /**
     * 16. Invoice unavailable for pending payment
     */
    public function test_16_invoice_unavailable_for_pending_payment(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-INVOICE-PENDING',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 150000,
            'status' => 'pending',
            'snap_token' => 'mock-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        $response = $this->get('/studio-foto-logic/booking/payment/' . $payment->order_id . '/invoice');
        $response->assertRedirect('/studio-foto-logic/booking/payment/' . $payment->order_id);
    }

    /**
     * 17. Invoice unavailable for failed payment
     */
    public function test_17_invoice_unavailable_for_failed_payment(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-INVOICE-FAILED',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 150000,
            'status' => 'gagal',
            'snap_token' => 'mock-token',
            'expired_at' => now()->subMinutes(15),
        ]);

        $response = $this->get('/studio-foto-logic/booking/payment/' . $payment->order_id . '/invoice');
        $response->assertRedirect('/studio-foto-logic');
    }

    /**
     * 18. Customer cannot select staff
     */
    public function test_18_customer_cannot_select_staff(): void
    {
        $staff = Staff::create([
            'idtenant' => $this->tenant->id,
            'name' => 'Photographer Pro',
            'is_active' => true,
        ]);

        $this->setBookingSession([$this->slot10->id], ['10:00']);

        $response = $this->post('/studio-foto-logic/booking/checkout', [
            'namapelanggan' => 'Joko',
            'email' => 'joko@example.com',
            'nomorhp' => '081234567800',
            'staff_id' => $staff->id, // Attempt to select staff
        ]);

        $booking = Booking::withoutGlobalScopes()->where('namapelanggan', 'Joko')->first();
        $this->assertNotNull($booking);
        $this->assertNull($booking->idstaff);
    }

    /**
     * 19. Selected addons rejected while feature disabled
     */
    public function test_19_selected_addons_rejected_while_feature_disabled(): void
    {
        $this->setBookingSession([$this->slot10->id], ['10:00']);

        $response = $this->post('/studio-foto-logic/booking/checkout', [
            'namapelanggan' => 'Kiki',
            'email' => 'kiki@example.com',
            'nomorhp' => '081234567801',
            'selected_addons' => ['frame-photo'],
        ]);

        $response->assertSessionHasErrors(['selected_addons']);
        $this->assertDatabaseMissing('payments', ['idtenant' => $this->tenant->id]);
    }

    /**
     * 20. Voucher rejected while feature disabled
     */
    public function test_20_voucher_rejected_while_feature_disabled(): void
    {
        $this->setBookingSession([$this->slot10->id], ['10:00']);

        $response = $this->post('/studio-foto-logic/booking/checkout', [
            'namapelanggan' => 'Lina',
            'email' => 'lina@example.com',
            'nomorhp' => '081234567802',
            'voucher_code' => 'DISKON50',
        ]);

        $response->assertSessionHasErrors(['voucher_code']);
        $this->assertDatabaseMissing('payments', ['idtenant' => $this->tenant->id]);
    }

    /**
     * 21. Late payment webhook cannot revive cancelled booking
     */
    public function test_21_late_payment_webhook_cannot_revive_cancelled_booking(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-TERMINAL-FAIL',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 150000,
            'status' => 'gagal',
            'snap_token' => 'mock-token',
            'expired_at' => now()->subMinutes(30),
        ]);

        $booking = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Maya',
            'email' => 'maya@example.com',
            'nomorhp' => '081234567803',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'cancelled',
        ]);

        /** @var MidtransPaymentService $midtransService */
        $midtransService = app(MidtransPaymentService::class);
        $midtransService->processSuccess($payment);

        $payment->refresh();
        $booking->refresh();

        // Must stay terminal 'gagal' and 'cancelled', no resurrection
        $this->assertEquals('gagal', $payment->status);
        $this->assertEquals('cancelled', $booking->status);
    }

    /**
     * 22. Malicious client callback cannot mark payment successful
     */
    public function test_22_malicious_client_callback_cannot_mark_payment_successful(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-MALICIOUS-TEST',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 150000,
            'status' => 'pending',
            'snap_token' => 'mock-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        $booking = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Hacker',
            'email' => 'hacker@example.com',
            'nomorhp' => '081234567804',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'pending',
        ]);

        // Mock MidtransPaymentService where verifyAndSync throws an error or returns pending
        $mockService = \Mockery::mock(MidtransPaymentService::class);
        $mockService->shouldReceive('verifyAndSync')->andThrow(new \Exception('Midtrans verification failed'));
        $this->app->instance(MidtransPaymentService::class, $mockService);

        // Client attempts to spoof settlement status in POST body
        $response = $this->postJson('/studio-foto-logic/booking/payment/' . $payment->order_id . '/callback', [
            'result' => [
                'status_code' => '200',
                'transaction_status' => 'settlement',
            ],
        ]);

        $payment->refresh();
        $booking->refresh();

        // Payment and booking MUST remain pending, not marked as success
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals('pending', $booking->status);
    }

    /**
     * 23. Multi-slot customer cancel is rejected
     */
    public function test_23_multi_slot_customer_cancel_is_rejected(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-MULTI-CANCEL-CHECK',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 300000,
            'status' => 'sukses',
            'snap_token' => 'mock-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        $b1 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'booking_code' => 'BK-MULTI-01',
            'cancellation_token' => 'valid-cancel-token-1',
            'namapelanggan' => 'Nino',
            'email' => 'nino@example.com',
            'nomorhp' => '081234567805',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
        ]);

        $b2 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'booking_code' => 'BK-MULTI-02',
            'cancellation_token' => 'valid-cancel-token-2',
            'namapelanggan' => 'Nino',
            'email' => 'nino@example.com',
            'nomorhp' => '081234567805',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'paid',
        ]);

        $this->assertTrue($b1->isMultiSlot());

        $response = $this->post('/manage/BK-MULTI-01/cancel?token=valid-cancel-token-1');
        $response->assertSessionHasErrors(['cancel']);

        $b1->refresh();
        $this->assertEquals('paid', $b1->status);
    }

    /**
     * 24. Multi-slot customer reschedule is rejected
     */
    public function test_24_multi_slot_customer_reschedule_is_rejected(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BKG-MULTI-RESCHEDULE-CHECK',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 300000,
            'status' => 'sukses',
            'snap_token' => 'mock-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        $b1 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'booking_code' => 'BK-MULTI-RESCHED-01',
            'reschedule_token' => 'valid-reschedule-token-1',
            'namapelanggan' => 'Oscar',
            'email' => 'oscar@example.com',
            'nomorhp' => '081234567806',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
        ]);

        $b2 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'booking_code' => 'BK-MULTI-RESCHED-02',
            'reschedule_token' => 'valid-reschedule-token-2',
            'namapelanggan' => 'Oscar',
            'email' => 'oscar@example.com',
            'nomorhp' => '081234567806',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'paid',
        ]);

        // Attempting to visit reschedule page should redirect back with error
        $showResponse = $this->get('/manage/BK-MULTI-RESCHED-01/reschedule?token=valid-reschedule-token-1');
        $showResponse->assertRedirect('/manage/BK-MULTI-RESCHED-01?token=valid-reschedule-token-1');
        $showResponse->assertSessionHasErrors(['reschedule']);

        // Attempting to post reschedule should be blocked with error
        $postResponse = $this->post('/manage/BK-MULTI-RESCHED-01/reschedule?token=valid-reschedule-token-1', [
            'tanggal' => $this->date,
            'schedule_id' => $this->slot14->id,
        ]);
        $postResponse->assertSessionHasErrors(['reschedule']);

        $b1->refresh();
        $this->assertEquals($this->slot10->id, $b1->idschedule);
    }
}
