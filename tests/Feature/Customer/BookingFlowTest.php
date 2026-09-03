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
        // Mock Midtrans Snap
        \Mockery::mock('alias:Midtrans\Snap', function ($mock) {
            $mock->shouldReceive('getSnapToken')->andReturn('mocked-snap-token');
        });

        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->service->id,
                'tanggal' => $this->tomorrow,
                'jam' => '10:00',
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
}
