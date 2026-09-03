<?php

namespace Tests\Feature\Owner;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use App\Services\MidtransPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CheckoutMidtransTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);
        
        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'My Midtrans Business',
            'jenisbisnis' => 'Klinik',
            'slug' => 'my-midtrans',
            'alamat' => 'Jalan Midtrans',
            'nomorhp' => '081234567890',
        ]);

        $this->plan = Plan::create([
            'namapaket' => 'pro',
            'hargabulanan' => 100000,
            'maxlayanan' => 10,
            'maxbooking' => 100,
            'isunlimited' => false,
        ]);
        
        $this->withSession(['current_tenant_id' => $this->tenant->id]);
    }
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_owner_can_initiate_checkout_and_generate_snap_token(): void
    {
        $snapMock = Mockery::mock('alias:Midtrans\Snap');
        $snapMock->shouldReceive('getSnapToken')->once()->andReturn('dummy_snap_token_123');

        $response = $this->actingAs($this->user)->withSession(['current_tenant_id' => $this->tenant->id])->post('/owner/checkout', [
            'plan_id' => $this->plan->id,
            'nama_pembayar' => 'John Doe',
            'email_pembayar' => 'john@example.com',
            'hp_pembayar' => '081234567890',
            'catatan' => 'Test payment',
        ]);
        
        
        $payment = Payment::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals('dummy_snap_token_123', $payment->snap_token);

        $response->assertRedirect(route('owner.checkout.payment', $payment));
    }
    
    public function test_owner_can_check_payment_status_success(): void
    {
        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'idplan' => $this->plan->id,
            'tipe' => 'subscription',
            'jumlah' => 100000,
            'status' => 'pending',
            'metode' => 'midtrans',
            'order_id' => 'BQ-20260806-0001',
            'expired_at' => now()->addHour(),
        ]);
        
        $mockService = $this->mock(MidtransPaymentService::class);
        $mockService->shouldReceive('verifyAndSync')
            ->once()
            ->andReturnUsing(function ($p) use ($payment) {
                $service = new MidtransPaymentService();
                return $service->syncStatus($payment, [
                    'transaction_status' => 'settlement',
                    'fraud_status' => 'accept',
                    'payment_type' => 'bank_transfer',
                ]);
            });

        $response = $this->actingAs($this->user)->withSession(['current_tenant_id' => $this->tenant->id])->postJson("/owner/checkout/{$payment->order_id}/check-status");
        
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'sukses',
        ]);
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'sukses',
            'metode' => 'bank_transfer',
        ]);
        
        $this->assertDatabaseHas('subscriptions', [
            'idtenant' => $this->tenant->id,
            'idplan' => $this->plan->id,
            'status' => 'active',
        ]);
    }
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_midtrans_webhook_updates_payment_status(): void
    {
        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'idplan' => $this->plan->id,
            'tipe' => 'subscription',
            'jumlah' => 100000,
            'status' => 'pending',
            'metode' => 'midtrans',
            'order_id' => 'BQ-20260806-0002',
            'expired_at' => now()->addHour(),
        ]);
        
        $serverKey = config('midtrans.server_key');
        $statusCode = '200';
        $grossAmount = '100000.00';
        
        $signatureKey = hash('sha512', $payment->order_id . $statusCode . $grossAmount . $serverKey);
        
        $payload = [
            'order_id' => $payment->order_id,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'gopay',
        ];
        
        $response = $this->postJson('/midtrans/webhook', $payload);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'sukses',
            'metode' => 'gopay',
        ]);
        
        $this->assertDatabaseHas('subscriptions', [
            'idtenant' => $this->tenant->id,
            'idplan' => $this->plan->id,
            'status' => 'active',
        ]);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_midtrans_webhook_updates_booking_status(): void
    {
        $service = \App\Models\Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Test Service',
            'harga' => 50000,
            'durasi' => 60,
        ]);

        $schedule = \App\Models\Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal' => now()->addDay()->format('Y-m-d'),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'tipe' => 'booking',
            'jumlah' => 50000,
            'status' => 'pending',
            'metode' => 'midtrans',
            'order_id' => 'BKG-20260806-0003',
            'expired_at' => now()->addHour(),
        ]);

        $booking = \App\Models\Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $service->id,
            'idschedule' => $schedule->id,
            'namapelanggan' => 'Test User',
            'nomorhp' => '081234567890',
            'email' => 'test@example.com',
            'tanggalbooking' => $schedule->tanggal,
            'jam' => $schedule->jam_mulai,
            'status' => 'pending',
            'idpayment' => $payment->id,
        ]);
        
        $serverKey = config('midtrans.server_key');
        $statusCode = '200';
        $grossAmount = '50000.00';
        
        $signatureKey = hash('sha512', $payment->order_id . $statusCode . $grossAmount . $serverKey);
        
        $payload = [
            'order_id' => $payment->order_id,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
        ];
        
        $response = $this->postJson('/midtrans/webhook', $payload);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'sukses',
            'metode' => 'qris',
        ]);
        
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'paid',
        ]);
    }

    public function test_owner_payment_page_renders_autodetect_indicator_and_correct_routes(): void
    {
        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'idplan' => $this->plan->id,
            'tipe' => 'subscription',
            'jumlah' => 100000,
            'status' => 'pending',
            'metode' => 'midtrans',
            'order_id' => 'BQ-OWNER-TEST-001',
            'expired_at' => now()->addHour(),
            'snap_token' => 'mocked-owner-snap-token',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get("/owner/checkout/{$payment->order_id}/payment");

        $response->assertStatus(200);
        $response->assertSee('Auto-detect aktif');
        $response->assertSee('BQ-OWNER-TEST-001');
        $response->assertSee("/owner/checkout/{$payment->order_id}/callback");
        $response->assertSee("/owner/checkout/{$payment->order_id}/check-status");
    }

    public function test_owner_check_payment_status_fast_path_when_already_paid(): void
    {
        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'idplan' => $this->plan->id,
            'tipe' => 'subscription',
            'jumlah' => 100000,
            'status' => 'sukses',
            'metode' => 'midtrans',
            'order_id' => 'BQ-OWNER-FASTPATH-001',
            'expired_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->postJson("/owner/checkout/{$payment->order_id}/check-status");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'sukses',
            'message' => 'Pembayaran berhasil dikonfirmasi!',
        ]);
        $this->assertStringContainsString("/owner/checkout/{$payment->order_id}/invoice", $response->json('redirect'));
    }

    public function test_midtrans_webhook_validates_signature_with_owner_custom_keys(): void
    {
        $customServerKey = 'SB-Mid-server-CUSTOM-SECRET-12345';

        // Set tenant to custom owner payment mode with custom server key
        $this->tenant->update([
            'payment_mode' => 'owner',
            'midtrans_environment' => 'sandbox',
            'midtrans_sandbox_server_key' => $customServerKey,
        ]);

        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'idplan' => $this->plan->id,
            'tipe' => 'booking',
            'jumlah' => 75000,
            'status' => 'pending',
            'metode' => 'midtrans',
            'order_id' => 'BKG-CUSTOM-KEY-001',
            'expired_at' => now()->addHour(),
        ]);

        $statusCode = '200';
        $grossAmount = '75000.00';

        // Sign with CUSTOM server key (not platform key)
        $signatureKey = hash('sha512', $payment->order_id . $statusCode . $grossAmount . $customServerKey);

        $payload = [
            'order_id' => $payment->order_id,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'gopay',
        ];

        $response = $this->postJson('/midtrans/webhook', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'sukses',
            'metode' => 'gopay',
        ]);
    }
}
