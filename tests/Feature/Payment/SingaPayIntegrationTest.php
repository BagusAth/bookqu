<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Actions\Booking\CreateBooking;
use App\Actions\Payment\CreateBookingPayment;
use App\Domain\Payment\PaymentState;
use App\Infrastructure\Payments\SingaPay\SingaPayWebhookVerifier;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SingaPayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected string $clientSecret = 'test_secret_key_1234567890';
    protected string $hmacKey = 'test_hmac_key_9876543210';
    protected string $apiKey = 'test_api_key_abc';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();

        Config::set('services.singapay.client_id', 'test_client_id');
        Config::set('services.singapay.client_secret', $this->clientSecret);
        Config::set('services.singapay.api_key', $this->apiKey);
        Config::set('services.singapay.hmac_validation_key', $this->hmacKey);
        Config::set('services.singapay.expiry_minutes', 15);
        Config::set('services.singapay.account_id', '01JTESTACCOUNTULID00000000000');

        $this->user = User::factory()->create();
        $this->tenant = Tenant::factory()->create([
            'iduser'       => $this->user->id,
            'payment_mode' => 'platform',
            'slug'         => 'studio-test',
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        parent::tearDown();
    }

    /**
     * Helper to dispatch signed SingaPay webhook request.
     */
    protected function postSingaPayWebhook(string $uri, array $payload, ?string $key = null, ?string $accessToken = 'dummy_jwt_token'): \Illuminate\Testing\TestResponse
    {
        $signingKey = $key ?? $this->hmacKey;
        $timestamp  = (string) time();

        /** @var SingaPayWebhookVerifier $verifier */
        $verifier = app(SingaPayWebhookVerifier::class);
        $signature = $verifier->computeSignature($uri, (string) $accessToken, $payload, $timestamp, $signingKey);

        return $this->withHeaders([
            'X-Signature'   => $signature,
            'X-Timestamp'   => $timestamp,
            'Authorization' => "Bearer {$accessToken}",
            'X-PARTNER-ID'  => $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->postJson($uri, $payload);
    }

    /**
     * Helper to create a sample webhook payload matching SingaPay documentation.
     */
    protected function makeWebhookPayload(string $orderId, float $amount, string $status = 'paid'): array
    {
        return [
            'status'  => 200,
            'success' => true,
            'event'   => 'payment-link-transaction',
            'data'    => [
                'transaction' => [
                    'reff_no'             => 'TX-' . $orderId,
                    'type'                => 'pl',
                    'status'              => $status,
                    'amount'              => [
                        'value'    => number_format($amount, 2, '.', ''),
                        'currency' => 'IDR',
                    ],
                    'tip'                 => null,
                    'post_timestamp'      => now()->toIso8601String(),
                    'processed_timestamp' => now()->toIso8601String(),
                ],
                'customer' => [
                    'id'    => null,
                    'name'  => 'Pelanggan Test',
                    'email' => 'pelanggan@test.com',
                    'phone' => '08123456789',
                ],
                'payment' => [
                    'method'          => 'payment_link',
                    'additional_info' => [
                        'payment_link' => [
                            'id'           => 999,
                            'reff_no'      => $orderId,
                            'payment_url'  => 'https://sandbox-paymentlink.singapay.id/b2b/' . $orderId,
                            'status'       => $status === 'paid' ? 'open' : 'closed',
                            'total_amount' => number_format($amount, 2, '.', ''),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_payment_link_creation(): void
    {
        Http::fake([
            '*access-token*' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => [
                    'access_token' => 'mocked-singapay-access-token-123',
                    'expires_in'   => 3600,
                ],
            ], 200),
            '*payment-link*' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => [
                    'id'          => 78910,
                    'reff_no'     => 'BKG-TEST-CREATE',
                    'payment_url' => 'https://sandbox-paymentlink.singapay.id/b2b/BKG-TEST-CREATE',
                ],
            ], 200),
        ]);

        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 150000]);

        /** @var CreateBookingPayment $bookingPayment */
        $bookingPayment = app(CreateBookingPayment::class);

        $customerData = [
            'namapelanggan' => 'Budi Santoso',
            'email'         => 'budi@example.com',
            'nomorhp'       => '081298765432',
        ];

        $payment = $bookingPayment->createPendingPayment($this->tenant, 150000, $customerData);

        $this->assertEquals(PaymentState::STATUS_PENDING, $payment->status);
        $this->assertEquals(PaymentState::METODE_SINGAPAY, $payment->metode);
        $this->assertEquals('singapay', $payment->provider);
        $this->assertNotNull($payment->order_id);
        $this->assertNotNull($payment->expired_at);

        $paymentUrl = $bookingPayment->generatePaymentLink($payment, $service, $customerData);

        $this->assertEquals('https://sandbox-paymentlink.singapay.id/b2b/BKG-TEST-CREATE', $paymentUrl);
        $payment->refresh();
        $this->assertEquals('https://sandbox-paymentlink.singapay.id/b2b/BKG-TEST-CREATE', $payment->payment_url);
        $this->assertEquals('78910', $payment->external_id);
    }

    public function test_successful_payment_webhook(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 100000]);
        $schedule = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '14:00:00',
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 100000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-SUCCESS-001',
            'payment_url'    => 'https://sandbox-paymentlink.singapay.id/b2b/BKG-SUCCESS-001',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Pelanggan Test',
            'email_pembayar' => 'pelanggan@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $booking = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Pelanggan Test',
            'nomorhp'        => '08123456789',
            'email'          => 'pelanggan@test.com',
            'tanggalbooking' => $schedule->tanggal,
            'jam'            => '14:00:00',
            'status'         => 'pending',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        $payload = $this->makeWebhookPayload($payment->order_id, 100000, 'paid');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $payment->refresh();
        $booking->refresh();

        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->status);
        $this->assertEquals('paid', $booking->status);
    }

    public function test_pending_webhook(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 100000]);
        $schedule = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '15:00:00',
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 100000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-PENDING-001',
            'payment_url'    => 'https://sandbox-paymentlink.singapay.id/b2b/BKG-PENDING-001',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Pelanggan Test',
            'email_pembayar' => 'pelanggan@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $booking = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Pelanggan Test',
            'nomorhp'        => '08123456789',
            'email'          => 'pelanggan@test.com',
            'tanggalbooking' => $schedule->tanggal,
            'jam'            => '15:00:00',
            'status'         => 'pending',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        $payload = $this->makeWebhookPayload($payment->order_id, 100000, 'pending');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(200);

        $payment->refresh();
        $booking->refresh();

        $this->assertEquals(PaymentState::STATUS_PENDING, $payment->status);
        $this->assertEquals('pending', $booking->status);
    }

    public function test_failed_or_expired_webhook(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 75000]);
        $schedule = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '16:00:00',
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 75000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-EXPIRED-001',
            'payment_url'    => 'https://sandbox-paymentlink.singapay.id/b2b/BKG-EXPIRED-001',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Pelanggan Test',
            'email_pembayar' => 'pelanggan@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $booking = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Pelanggan Test',
            'nomorhp'        => '08123456789',
            'email'          => 'pelanggan@test.com',
            'tanggalbooking' => $schedule->tanggal,
            'jam'            => '16:00:00',
            'status'         => 'pending',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        $payload = $this->makeWebhookPayload($payment->order_id, 75000, 'expired');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(200);

        $payment->refresh();
        $booking->refresh();

        $this->assertEquals(PaymentState::STATUS_GAGAL, $payment->status);
        $this->assertEquals('cancelled', $booking->status);
    }

    public function test_invalid_hmac_rejected(): void
    {
        $payload = $this->makeWebhookPayload('BKG-INVALID-HMAC', 50000, 'paid');

        // Post with wrong secret key to induce invalid signature
        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload, 'wrong_secret_key');

        $response->assertStatus(401);
        $response->assertJson(['status' => 'error', 'message' => 'Invalid signature']);
    }

    public function test_invalid_amount_rejected(): void
    {
        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 100000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-AMOUNT-MISMATCH',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Pelanggan Test',
            'email_pembayar' => 'pelanggan@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        // Incoming payload contains manipulated amount (e.g. 10000 instead of 100000)
        $payload = $this->makeWebhookPayload($payment->order_id, 10000, 'paid');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error', 'message' => 'Transaction amount mismatch']);

        $payment->refresh();
        $this->assertEquals(PaymentState::STATUS_PENDING, $payment->status);
    }

    public function test_unknown_order_id(): void
    {
        $payload = $this->makeWebhookPayload('NONEXISTENT-ORDER-ID', 100000, 'paid');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(404);
        $response->assertJson(['status' => 'error', 'message' => 'Payment not found']);
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 50000]);
        $schedule = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '17:00:00',
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-IDEMPOTENT-001',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Pelanggan Test',
            'email_pembayar' => 'pelanggan@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $booking = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Pelanggan Test',
            'nomorhp'        => '08123456789',
            'email'          => 'pelanggan@test.com',
            'tanggalbooking' => $schedule->tanggal,
            'jam'            => '17:00:00',
            'status'         => 'pending',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        $payload = $this->makeWebhookPayload($payment->order_id, 50000, 'paid');

        // First delivery
        $res1 = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);
        $res1->assertStatus(200);

        $payment->refresh();
        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->status);

        // Second delivery (duplicate retry)
        $res2 = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);
        $res2->assertStatus(200);
        $res2->assertJson(['status' => 'success']);

        $payment->refresh();
        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->status);
    }

    public function test_multi_slot_booking_webhook(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 50000]);
        $schedule1 = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '10:00:00',
        ]);
        $schedule2 = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '11:00:00',
        ]);

        $totalAmount = 100000.00; // 2 slots x 50,000

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => $totalAmount,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-MULTISLOT-001',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Pelanggan Multi',
            'email_pembayar' => 'multi@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $booking1 = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule1->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Pelanggan Multi',
            'nomorhp'        => '08123456789',
            'email'          => 'multi@test.com',
            'tanggalbooking' => $schedule1->tanggal,
            'jam'            => '10:00:00',
            'status'         => 'pending',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        $booking2 = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule2->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Pelanggan Multi',
            'nomorhp'        => '08123456789',
            'email'          => 'multi@test.com',
            'tanggalbooking' => $schedule2->tanggal,
            'jam'            => '11:00:00',
            'status'         => 'pending',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        $payload = $this->makeWebhookPayload($payment->order_id, $totalAmount, 'paid');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(200);

        $payment->refresh();
        $booking1->refresh();
        $booking2->refresh();

        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->status);
        $this->assertEquals('paid', $booking1->status);
        $this->assertEquals('paid', $booking2->status);
    }

    public function test_free_booking_remains_functional(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 0]);
        $schedule = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDays(2)->toDateString(),
            'jam_mulai' => '09:00:00',
            'status'    => 'tersedia',
        ]);

        /** @var CreateBooking $createBooking */
        $createBooking = app(CreateBooking::class);

        $customerData = [
            'namapelanggan' => 'Free User',
            'email'         => 'free@example.com',
            'nomorhp'       => '081234567890',
            'catatan'       => 'Free session test',
        ];

        $result = $createBooking->execute(
            $this->tenant,
            $service,
            is_string($schedule->tanggal) ? $schedule->tanggal : $schedule->tanggal->toDateString(),
            ['09:00'],
            [$schedule->id],
            $customerData
        );

        $this->assertTrue($result['free']);
        $this->assertNotNull($result['payment']);
        $this->assertEquals(PaymentState::STATUS_SUKSES, $result['payment']->status);
        $this->assertEquals(PaymentState::METODE_GRATIS, $result['payment']->metode);

        $this->assertCount(1, $result['bookings']);
        $this->assertEquals('paid', $result['bookings'][0]->status);
    }

    public function test_tenant_isolation_in_webhook(): void
    {
        $otherUser = User::factory()->create();
        $otherTenant = Tenant::factory()->create([
            'iduser'       => $otherUser->id,
            'payment_mode' => 'platform',
            'slug'         => 'studio-other',
        ]);

        $service = Service::factory()->create(['idtenant' => $otherTenant->id, 'harga' => 50000]);
        $schedule = Schedule::factory()->create([
            'idtenant'  => $otherTenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '11:00:00',
        ]);

        $payment = Payment::create([
            'idtenant'       => $otherTenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-OTHER-TENANT-001',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Pelanggan Other',
            'email_pembayar' => 'other@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $booking = Booking::create([
            'idtenant'       => $otherTenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Pelanggan Other',
            'nomorhp'        => '08123456789',
            'email'          => 'other@test.com',
            'tanggalbooking' => $schedule->tanggal,
            'jam'            => '11:00:00',
            'status'         => 'pending',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        // Webhook comes in without tenant context initially set
        app(TenantContext::class)->clear();

        $payload = $this->makeWebhookPayload($payment->order_id, 50000, 'paid');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(200);

        $payment->refresh();
        $booking->refresh();

        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->status);
        $this->assertEquals('paid', $booking->status);
        $this->assertEquals($otherTenant->id, $payment->idtenant);
        $this->assertEquals($otherTenant->id, $booking->idtenant);

        // Verify context was cleared after webhook handling
        $this->assertFalse(app(TenantContext::class)->hasTenant());
    }
}
