<?php

namespace Tests\Feature\Customer;

use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        // Create tenant
        $this->user = User::factory()->create(['role' => 'owner']);
        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'My Booking Business',
            'jenisbisnis' => 'Klinik',
            'slug' => 'my-business',
            'alamat' => 'Jalan Test',
            'nomorhp' => '08123456789',
        ]);

        // Create service
        $this->service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Program Konsultasi',
            'harga' => 200000,
            'durasi' => 60,
            'is_active' => true,
        ]);

        // Create available schedule for tomorrow
        $this->tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $this->schedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->tomorrow,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);
    }

    public function test_customer_can_see_programs_and_select_one(): void
    {
        $response = $this->get('/my-business');
        $response->assertStatus(200);
        $response->assertSee('Program Konsultasi');
        $response->assertSee('"duration":60', false);
        $response->assertSee('"duration_unit":"menit"', false);

        $responseSelect = $this->post('/my-business/booking/select-program', [
            'service_id' => $this->service->id,
        ]);

        $responseSelect->assertRedirect('/my-business/booking/date');
        $this->assertEquals($this->service->id, session('booking.service_id'));
    }

    public function test_customer_can_select_date_after_program(): void
    {
        // Simulate session state from previous step
        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
            ]
        ]);

        $response = $this->get('/my-business/booking/date');
        $response->assertStatus(200);

        $responseSelect = $this->post('/my-business/booking/select-date', [
            'tanggal' => $this->tomorrow,
        ]);

        $responseSelect->assertRedirect('/my-business/booking/time');
        $this->assertEquals($this->tomorrow, session('booking.tanggal'));
    }

    public function test_customer_can_select_time_and_proceed_to_checkout(): void
    {
        // Simulate session state from previous steps
        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->tomorrow,
            ]
        ]);

        $response = $this->get('/my-business/booking/time');
        $response->assertStatus(200);

        $responseSelect = $this->post('/my-business/booking/select-time', [
            'jam' => '10:00',
            'schedule_id' => $this->schedule->id,
        ]);

        $responseSelect->assertRedirect('/my-business/booking/checkout');
        $this->assertEquals('10:00', session('booking.jam'));
        
        $responseCheckout = $this->get('/my-business/booking/checkout');
        $responseCheckout->assertStatus(200);
        $responseCheckout->assertSee('Isi Data Diri');
    }

    public function test_customer_can_process_checkout(): void
    {
        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->tomorrow,
                'jam' => '10:00',
                'schedule_id' => $this->schedule->id,
            ]
        ]);

        $response = $this->post('/my-business/booking/checkout', [
            'namapelanggan' => 'John Doe',
            'email' => 'john@example.com',
            'nomorhp' => '08123456789',
            'catatan' => 'Tolong tepat waktu',
        ]);

        $payment = \App\Models\Payment::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('mocked-snap-token', $payment->snap_token);

        $booking = \App\Models\Booking::withoutGlobalScopes()->where('idpayment', $payment->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals('John Doe', $booking->namapelanggan);
        $this->assertEquals('pending', $booking->status);

        $response->assertRedirect('/my-business/booking/payment/' . $payment->order_id);
    }

    public function test_customer_payment_page_renders_realtime_indicator_and_correct_routes(): void
    {
        $payment = \App\Models\Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-TEST-12345',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 100000,
            'status' => 'pending',
            'snap_token' => 'mocked-snap-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        $response = $this->get('/my-business/booking/payment/' . $payment->order_id);
        $response->assertStatus(200);
        $response->assertSee('Sistem memantau pembayaran Anda secara otomatis');
        $response->assertSee('BQ-TEST-12345');
        // Ensure the generated route contains the order_id, not a raw numerical id
        $response->assertSee('/my-business/booking/payment/BQ-TEST-12345/callback');
        $response->assertSee('/my-business/booking/payment/BQ-TEST-12345/check-status');
    }

    public function test_customer_check_payment_status_fast_path_when_already_paid(): void
    {
        $payment = \App\Models\Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-TEST-FASTPATH',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 100000,
            'status' => 'sukses',
            'snap_token' => 'mocked-snap-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/my-business/booking/payment/' . $payment->order_id . '/check-status');
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'sukses',
            'message' => 'Pembayaran berhasil dikonfirmasi!',
        ]);
        $this->assertStringContainsString('/my-business/booking/payment/' . $payment->order_id . '/invoice', $response->json('redirect'));
    }

    public function test_customer_handle_callback_success(): void
    {
        $payment = \App\Models\Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-TEST-CALLBACK',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 100000,
            'status' => 'pending',
            'snap_token' => 'mocked-snap-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        // Mock MidtransPaymentService to return sukses on verifyAndSync
        $mockService = \Mockery::mock(\App\Services\MidtransPaymentService::class);
        $mockService->shouldReceive('verifyAndSync')->once()->andReturn([
            'status' => 'sukses',
            'message' => 'Pembayaran berhasil dikonfirmasi!',
            'payment' => $payment,
        ]);
        $this->app->instance(\App\Services\MidtransPaymentService::class, $mockService);

        $response = $this->postJson('/my-business/booking/payment/' . $payment->order_id . '/callback', [
            'result' => [
                'status_code' => '200',
                'transaction_status' => 'settlement',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'sukses',
            'message' => 'Pembayaran berhasil dikonfirmasi!',
        ]);
        $this->assertStringContainsString('/my-business/booking/payment/' . $payment->order_id . '/invoice', $response->json('redirect'));
    }

    public function test_customer_can_validate_voucher(): void
    {
        $voucher = \App\Models\Voucher::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'code' => 'HEMAT20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'min_spending' => 100000,
            'max_discount' => 50000,
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/my-business/booking/validate-voucher', [
            'voucher_code' => 'HEMAT20',
            'amount' => 200000,
            'service_id' => $this->service->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'code' => 'HEMAT20',
            'discount_amount' => 40000, // 20% of 200k = 40k
        ]);
    }

    public function test_checkout_with_valid_voucher_applies_discount(): void
    {
        $voucher = \App\Models\Voucher::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'code' => 'POTONG50',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'min_spending' => 150000,
            'usage_limit' => 5,
            'used_count' => 0,
            'is_active' => true,
        ]);

        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->tomorrow,
                'jam' => '10:00',
                'schedule_id' => $this->schedule->id,
            ]
        ]);

        $response = $this->post('/my-business/booking/checkout', [
            'namapelanggan' => 'Jane Doe',
            'email' => 'jane@example.com',
            'nomorhp' => '081298765432',
            'voucher_code' => 'POTONG50',
        ]);

        $payment = \App\Models\Payment::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->latest()->first();
        $this->assertNotNull($payment);
        // Original 200k - 50k voucher = 150k
        $this->assertEquals(150000, (int) $payment->jumlah);

        // Voucher used count should be incremented
        $this->assertEquals(1, $voucher->fresh()->used_count);
    }

    public function test_invoice_renders_calendar_and_back_buttons(): void
    {
        $payment = \App\Models\Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-TEST-INVOICE',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 200000,
            'status' => 'sukses',
            'snap_token' => 'mocked-snap-token',
            'expired_at' => now()->addMinutes(15),
        ]);

        $booking = \App\Models\Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Alice',
            'email' => 'alice@example.com',
            'nomorhp' => '08123456789',
            'tanggalbooking' => $this->tomorrow,
            'jam' => '10:00',
            'status' => 'paid',
            'booking_code' => 'BKQ-INV-001',
            'cancellation_token' => 'token123',
        ]);

        $response = $this->get('/my-business/booking/payment/' . $payment->order_id . '/invoice');
        $response->assertStatus(200);
        $response->assertSee('Booking Berhasil Dikonfirmasi!');
        $response->assertSee('Google Calendar');
        $response->assertSee('Apple / Outlook (.ics)');
        $response->assertSee('Bagikan ke WhatsApp');
        $response->assertSee('Kembali ke Beranda');
        // Check header back button
        $response->assertSee('/my-business');
    }

    public function test_invoice_renders_even_when_booking_code_is_initially_null(): void
    {
        $payment = \App\Models\Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-TEST-NULL-CODE',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 200000,
            'status' => 'sukses',
            'snap_token' => 'mocked-snap-token-2',
            'expired_at' => now()->addMinutes(15),
        ]);

        $booking = \App\Models\Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Bob',
            'email' => 'bob@example.com',
            'nomorhp' => '08123456780',
            'tanggalbooking' => $this->tomorrow,
            'jam' => '10:00',
            'status' => 'paid',
            'booking_code' => null, // Initially null as reported by user
            'cancellation_token' => null,
        ]);

        $response = $this->get('/my-business/booking/payment/' . $payment->order_id . '/invoice');
        $response->assertStatus(200);
        $response->assertSee('Booking Berhasil Dikonfirmasi!');

        $booking->refresh();
        $this->assertNotNull($booking->booking_code);
        $this->assertNotNull($booking->cancellation_token);
    }

    public function test_customer_can_cancel_pending_payment_and_release_booking_slot(): void
    {
        $payment = \App\Models\Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-TEST-CANCEL',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 200000,
            'status' => 'pending',
            'snap_token' => 'mocked-snap-token-3',
            'expired_at' => now()->addMinutes(15),
        ]);

        $booking = \App\Models\Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Charlie',
            'email' => 'charlie@example.com',
            'nomorhp' => '08123456781',
            'tanggalbooking' => $this->tomorrow,
            'jam' => '10:00',
            'status' => 'pending',
        ]);

        $response = $this->post('/my-business/booking/payment/' . $payment->order_id . '/cancel');
        $response->assertRedirect('/my-business');
        $response->assertSessionHas('info');

        $payment->refresh();
        $booking->refresh();

        $this->assertEquals('gagal', $payment->status);
        $this->assertEquals('cancelled', $booking->status);
    }
}

