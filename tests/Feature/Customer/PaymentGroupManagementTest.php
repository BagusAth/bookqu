<?php

namespace Tests\Feature\Customer;

use App\Mail\BookingGroupInvoiceMail;
use App\Mail\BookingInvoiceMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MidtransPaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Service $service;
    protected string $date;
    protected Schedule $slot10;
    protected Schedule $slot11;
    protected Schedule $slot12;

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
            'namabisnis' => 'Studio Foto Group',
            'jenisbisnis' => 'Studio Foto',
            'slug' => 'studio-foto-group',
            'alamat' => 'Jl. Merdeka No. 45',
            'nomorhp' => '081234567890',
            'cancel_before_hours' => 24,
            'reschedule_before_hours' => 24,
        ]);

        $this->service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Foto Studio Sesi',
            'harga' => 100000,
            'durasi' => 60,
            'satuan_durasi' => 'menit',
            'is_active' => true,
        ]);

        $this->date = Carbon::tomorrow('Asia/Jakarta')->toDateString();

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
    }

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

    public function test_multi_slot_payment_has_one_management_token(): void
    {
        $this->setBookingSession([$this->slot10->id, $this->slot11->id], ['10:00', '11:00']);

        $response = $this->post("/{$this->tenant->slug}/booking/checkout", [
            'idlayanan' => $this->service->id,
            'idschedules' => [$this->slot10->id, $this->slot11->id],
            'namapelanggan' => 'Alice Group',
            'nomorhp' => '081299990001',
            'email' => 'alice@group.test',
        ]);

        $payment = Payment::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->latest()->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->manage_token);
        $this->assertEquals(64, strlen($payment->manage_token));

        $bookings = Booking::withoutGlobalScopes()->where('idpayment', $payment->id)->get();
        $this->assertCount(2, $bookings);

        $manageUrl = $payment->getManageUrl();
        $this->assertStringContainsString('/manage/payment/' . $payment->order_id, $manageUrl);
        $this->assertStringContainsString('token=' . $payment->manage_token, $manageUrl);
    }

    public function test_payment_management_url_valid_and_shows_all_slots(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-GRP-' . time(),
            'manage_token' => Booking::generateSecureToken(),
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'status' => 'sukses',
            'jumlah' => 200000,
        ]);

        $b1 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Charlie',
            'email' => 'charlie@test.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
            'booking_code' => Booking::generateBookingCode(),
            'cancellation_token' => Booking::generateSecureToken(),
        ]);

        $b2 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Charlie',
            'email' => 'charlie@test.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'paid',
            'booking_code' => Booking::generateBookingCode(),
            'cancellation_token' => Booking::generateSecureToken(),
        ]);

        $response = $this->get('/manage/payment/' . $payment->order_id . '?token=' . $payment->manage_token);
        $response->assertStatus(200);
        $response->assertSee($payment->order_id);
        $response->assertSee('10:00');
        $response->assertSee('11:00');
        $response->assertSee('Multi-Slot');
    }

    public function test_payment_management_invalid_token_returns_403(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-SEC-' . time(),
            'manage_token' => Booking::generateSecureToken(),
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'status' => 'sukses',
            'jumlah' => 100000,
        ]);

        Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Hacker',
            'email' => 'hacker@test.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
        ]);

        // Wrong token
        $response = $this->get('/manage/payment/' . $payment->order_id . '?token=wrong-token');
        $response->assertStatus(403);

        // Missing token
        $responseNoToken = $this->get('/manage/payment/' . $payment->order_id);
        $responseNoToken->assertStatus(403);
    }

    public function test_payment_management_cross_tenant_rejected(): void
    {
        $otherTenant = Tenant::create([
            'iduser' => User::factory()->create()->id,
            'namabisnis' => 'Other Biz',
            'jenisbisnis' => 'Studio Foto',
            'slug' => 'other-biz',
            'alamat' => 'Jl. Lain No. 1',
            'nomorhp' => '081299990000',
        ]);

        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-XTE-' . time(),
            'manage_token' => Booking::generateSecureToken(),
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'status' => 'sukses',
            'jumlah' => 100000,
        ]);

        // Booking belongs to otherTenant while payment belongs to $this->tenant
        Booking::withoutGlobalScopes()->create([
            'idtenant' => $otherTenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Inconsistent',
            'email' => 'inconsistent@test.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
        ]);

        $response = $this->get('/manage/payment/' . $payment->order_id . '?token=' . $payment->manage_token);
        $response->assertStatus(403);
    }

    public function test_legacy_booking_management_link_remains_functional(): void
    {
        $booking = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'namapelanggan' => 'Legacy Customer',
            'email' => 'legacy@test.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
            'booking_code' => Booking::generateBookingCode(),
            'cancellation_token' => Booking::generateSecureToken(),
        ]);

        $response = $this->get('/manage/' . $booking->booking_code . '?token=' . $booking->cancellation_token);
        $response->assertStatus(200);
        $response->assertSee($booking->booking_code);
    }

    public function test_multi_slot_payment_sends_only_one_customer_email(): void
    {
        Mail::fake();

        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-EMAIL-' . time(),
            'manage_token' => Booking::generateSecureToken(),
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'status' => 'pending',
            'jumlah' => 200000,
            'email_pembayar' => 'single-email@customer.com',
        ]);

        $b1 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Single Mail',
            'email' => 'single-email@customer.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'pending',
        ]);

        $b2 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Single Mail',
            'email' => 'single-email@customer.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'pending',
        ]);

        $service = app(MidtransPaymentService::class);
        $result = $service->processSuccess($payment, 'gopay', 'settlement');

        $this->assertEquals('sukses', $result['status']);
        $b1->refresh();
        $b2->refresh();
        $this->assertEquals('paid', $b1->status);
        $this->assertEquals('paid', $b2->status);

        // Exactly 1 consolidated email sent to customer
        Mail::assertSent(BookingGroupInvoiceMail::class, 1);
        Mail::assertNotSent(BookingInvoiceMail::class);
    }

    public function test_pending_payment_with_cancelled_booking_does_not_resurrect_on_settlement(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-LATE-' . time(),
            'manage_token' => Booking::generateSecureToken(),
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'status' => 'pending',
            'jumlah' => 200000,
        ]);

        $b1 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Cancelled One',
            'email' => 'cancelled@customer.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'cancelled', // Already cancelled by timeout/admin
        ]);

        $b2 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Pending One',
            'email' => 'cancelled@customer.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'pending',
        ]);

        $service = app(MidtransPaymentService::class);
        $result = $service->processSuccess($payment, 'qris', 'settlement');

        // Settlement rejected to avoid resurrection
        $this->assertEquals('gagal', $result['status']);

        $payment->refresh();
        $b1->refresh();
        $b2->refresh();

        $this->assertNotEquals('sukses', $payment->status);
        $this->assertEquals('cancelled', $b1->status);
        $this->assertEquals('pending', $b2->status); // Not marked as paid
    }

    public function test_failed_payment_settlement_remains_failed(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-FAIL-' . time(),
            'manage_token' => Booking::generateSecureToken(),
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'status' => 'gagal',
            'jumlah' => 100000,
        ]);

        $service = app(MidtransPaymentService::class);
        $result = $service->processSuccess($payment, 'qris', 'settlement');

        $this->assertEquals('gagal', $result['status']);
        $payment->refresh();
        $this->assertEquals('gagal', $payment->status);
    }

    public function test_idempotent_duplicate_settlement(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-IDEM-' . time(),
            'manage_token' => Booking::generateSecureToken(),
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'status' => 'sukses',
            'jumlah' => 100000,
        ]);

        Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Paid User',
            'email' => 'paid@customer.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
        ]);

        $service = app(MidtransPaymentService::class);
        $result = $service->processSuccess($payment, 'bank_transfer', 'settlement');

        $this->assertEquals('sukses', $result['status']);
        $this->assertStringContainsString('sebelumnya', $result['message']);
    }

    public function test_booking_price_label_reflects_slot_price(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-PRC-' . time(),
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'status' => 'sukses',
            'jumlah' => 300000, // Total for 3 slots
        ]);

        $b1 = Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Test Price',
            'email' => 'price@test.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
        ]);

        // Service harga is 100,000. Price label must be Rp 100.000, NOT Rp 300.000!
        $this->assertEquals('Rp 100.000', $b1->priceLabel);
    }

    public function test_owner_customer_spending_sums_distinct_payments(): void
    {
        $ownerUser = $this->tenant->user;

        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-SPEND-' . time(),
            'manage_token' => Booking::generateSecureToken(),
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'status' => 'sukses',
            'jumlah' => 200000, // Multi-slot total
        ]);

        Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'VIP Customer',
            'email' => 'vip@customer.com',
            'nomorhp' => '081200001111',
            'tanggalbooking' => $this->date,
            'jam' => '10:00',
            'status' => 'paid',
        ]);

        Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot11->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'VIP Customer',
            'email' => 'vip@customer.com',
            'nomorhp' => '081200001111',
            'tanggalbooking' => $this->date,
            'jam' => '11:00',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($ownerUser)->get(route('owner.customers.detail', ['identifier' => 'vip@customer.com']));
        $response->assertStatus(200);
        $data = $response->json();

        // Spending should be 200,000, NOT 400,000 (which would happen if joined per booking without distinct payment)
        $this->assertEquals(200000, $data['total_spent']);
        $this->assertEquals('Rp 200.000', $data['formatted_spent']);
    }

    public function test_payment_group_invoice_renders_without_carbon_double_time_exception(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-INV-TEST-' . time(),
            'manage_token' => Booking::generateSecureToken(),
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'status' => 'sukses',
            'jumlah' => 200000,
        ]);

        Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->slot10->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Invoice Customer',
            'email' => 'invoice@customer.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => $this->date,
            'jam' => '10:00:00',
            'status' => 'paid',
            'booking_code' => 'BKQ-INV-1',
        ]);

        $response = $this->get('/manage/payment/' . $payment->order_id . '/invoice?token=' . $payment->manage_token);
        $response->assertStatus(200);
        $response->assertSee('Bukti Reservasi Resmi');
        $response->assertSee($payment->order_id);
    }

    public function test_past_slots_on_today_are_not_counted_as_available_in_date_selection(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 14:00:00', 'Asia/Jakarta'));

        $today = '2026-09-22';

        // Slot 1: past (09:00 - 10:00)
        Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $today,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00',
            'status' => 'tersedia',
        ]);

        // Slot 2: future (16:00 - 17:00)
        Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $today,
            'jam_mulai' => '16:00:00',
            'jam_selesai' => '17:00:00',
            'status' => 'tersedia',
        ]);

        $response = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
            ],
        ])->get('/' . $this->tenant->slug . '/booking/date');

        $response->assertStatus(200);
        $payload = $response->viewData('availabilityPayload');

        $todayAvailability = collect($payload)->firstWhere('date', $today);
        $this->assertNotNull($todayAvailability);
        $this->assertEquals(2, $todayAvailability['total_slots']);
        // Only 1 slot (16:00) is available, slot 09:00 must NOT be counted!
        $this->assertEquals(1, $todayAvailability['available_slots']);

        Carbon::setTestNow(); // reset
    }

    public function test_today_with_only_past_slots_shows_zero_available_and_select_date_fails(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 18:00:00', 'Asia/Jakarta'));

        $today = '2026-09-22';

        // Only past slots (10:00 and 11:00)
        Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $today,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        // 1. Check date selection availability payload
        $response = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
            ],
        ])->get('/' . $this->tenant->slug . '/booking/date');

        $response->assertStatus(200);
        $payload = $response->viewData('availabilityPayload');
        $todayAvailability = collect($payload)->firstWhere('date', $today);

        $this->assertNotNull($todayAvailability);
        $this->assertEquals(1, $todayAvailability['total_slots']);
        $this->assertEquals(0, $todayAvailability['available_slots']);

        // 2. Submitting selectDate for today should fail
        $selectResponse = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
            ],
        ])->post('/' . $this->tenant->slug . '/booking/select-date', [
            'tanggal' => $today,
        ]);

        $selectResponse->assertSessionHasErrors('tanggal');

        Carbon::setTestNow(); // reset
    }
}
