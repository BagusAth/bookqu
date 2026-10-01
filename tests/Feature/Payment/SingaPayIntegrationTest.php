<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Actions\Booking\CreateBooking;
use App\Actions\Payment\CreateBookingPayment;
use App\Domain\Payment\PaymentState;
use App\Infrastructure\Payments\SingaPay\SingaPayClient;
use App\Infrastructure\Payments\SingaPay\SingaPayPaymentGateway;
use App\Infrastructure\Payments\SingaPay\SingaPayWebhookVerifier;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
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
    protected string $accountId = '01JTESTACCOUNTULID00000000000';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();

        Config::set('services.singapay.base_url', 'https://payment-b2b.singapay.id');
        Config::set('services.singapay.client_id', 'test_client_id');
        Config::set('services.singapay.client_secret', $this->clientSecret);
        Config::set('services.singapay.api_key', $this->apiKey);
        Config::set('services.singapay.hmac_validation_key', $this->hmacKey);
        Config::set('services.singapay.expiry_minutes', 15);
        Config::set('services.singapay.account_id', $this->accountId);
        Config::set('services.singapay.webhook_tolerance_seconds', 300);

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
    protected function postSingaPayWebhook(
        string $uri,
        array $payload,
        ?string $key = null,
        ?string $accessToken = 'dummy_jwt_token',
        ?string $timestamp = null
    ): \Illuminate\Testing\TestResponse {
        $signingKey = $key ?? $this->clientSecret;
        $ts         = $timestamp ?? (string) time();

        /** @var SingaPayWebhookVerifier $verifier */
        $verifier = app(SingaPayWebhookVerifier::class);
        $signature = $verifier->computeSignature($uri, (string) $accessToken, $payload, $ts, $signingKey);

        return $this->withHeaders([
            'X-Signature'   => $signature,
            'X-Timestamp'   => $ts,
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
                    'reff_no'             => $orderId,
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
                            'payment_url'  => 'https://payment-link.singapay.id/b2b/' . $orderId,
                            'status'       => $status === 'paid' ? 'open' : 'closed',
                            'total_amount' => number_format($amount, 2, '.', ''),
                        ],
                    ],
                ],
            ],
        ];
    }

    // ==========================================
    // 1. AUTHENTICATION CONTRACT TESTS
    // ==========================================

    public function test_oauth_token_request_contract(): void
    {
        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => [
                    'access_token' => 'valid-singapay-b2b-jwt-token',
                    'token_type'   => 'Bearer',
                    'expires_in'   => 3600,
                ],
            ], 200),
        ]);

        /** @var SingaPayClient $client */
        $client = app(SingaPayClient::class);
        $token = $client->getAccessToken();

        $this->assertEquals('valid-singapay-b2b-jwt-token', $token);

        Http::assertSent(function (Request $request) {
            $expectedBasicAuth = 'Basic ' . base64_encode('test_client_id:' . $this->clientSecret);

            return $request->url() === 'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b'
                && $request->method() === 'POST'
                && $request->header('Authorization')[0] === $expectedBasicAuth
                && $request->header('X-PARTNER-ID')[0] === $this->apiKey
                && $request->header('Content-Type')[0] === 'application/json'
                && $request['grant_type'] === 'client_credentials';
        });
    }

    public function test_oauth_token_failure_throws_exception_without_leaking_credentials(): void
    {
        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 401,
                'success' => false,
                'message' => 'Unauthorized client credentials',
            ], 401),
        ]);

        /** @var SingaPayClient $client */
        $client = app(SingaPayClient::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to obtain SingaPay access token: HTTP 401');

        $client->getAccessToken();
    }

    // ==========================================
    // 2. PAYMENT LINK API CONTRACT TESTS
    // ==========================================

    public function test_payment_link_creation_contract_and_payload(): void
    {
        $expectedPaymentUrl = 'https://payment-link.singapay.id/b2b/BKG-TEST-CREATE';
        $expectedExternalId = '78910';

        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => [
                    'access_token' => 'mocked-jwt-token',
                    'expires_in'   => 3600,
                ],
            ], 200),
            'https://payment-b2b.singapay.id/api/v1.0/payment-link-manage/' . $this->accountId => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => [
                    'id'          => $expectedExternalId,
                    'reff_no'     => 'BKG-TEST-CREATE',
                    'payment_url' => $expectedPaymentUrl,
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

        // Verification 1: Payment record starts pending
        $this->assertEquals(PaymentState::STATUS_PENDING, $payment->status);
        $this->assertEquals(PaymentState::METODE_SINGAPAY, $payment->metode);
        $this->assertEquals('singapay', $payment->provider);
        $this->assertNotNull($payment->order_id);
        $this->assertNotNull($payment->expired_at);

        $paymentUrl = $bookingPayment->generatePaymentLink($payment, $service, $customerData);

        // Verification 2: Payment URL returned and saved, external_id stored
        $this->assertEquals($expectedPaymentUrl, $paymentUrl);
        $payment->refresh();
        $this->assertEquals($expectedPaymentUrl, $payment->payment_url);
        $this->assertEquals($expectedExternalId, $payment->external_id);

        // Verification 3: Status MUST NOT be marked as paid/sukses just because link was created!
        $this->assertEquals(PaymentState::STATUS_PENDING, $payment->status);

        // Verification 4: SingaPay API request contract
        Http::assertSent(function (Request $request) use ($payment) {
            if ($request->url() !== "https://payment-b2b.singapay.id/api/v1.0/payment-link-manage/{$this->accountId}") {
                return false;
            }

            $payload = $request->data();

            return $request->method() === 'POST'
                && $request->header('Authorization')[0] === 'Bearer mocked-jwt-token'
                && $request->header('X-PARTNER-ID')[0] === $this->apiKey
                && $payload['reff_no'] === (string) $payment->order_id
                && isset($payload['title'])
                && $payload['max_usage'] === 1
                && $payload['total_amount'] === 150000
                && is_array($payload['items'])
                && count($payload['items']) === 1
                && $payload['items'][0]['quantity'] === 1
                && $payload['items'][0]['unit_price'] === 150000
                && is_int($payload['expired_at']) // epoch milliseconds
                && $payload['expired_at'] > 1000000000000
                && $payload['required_customer_detail'] === true
                && array_key_exists('customer_pays_fee', $payload)
                && isset($payload['success_redirect_url'])
                && isset($payload['expired_redirect_url'])
                && isset($payload['optional_metadata']['order_id']);
        });
    }

    public function test_payment_link_creation_error_handling(): void
    {
        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => ['access_token' => 'mocked-jwt-token', 'expires_in' => 3600],
            ], 200),
            'https://payment-b2b.singapay.id/api/v1.0/payment-link-manage/' . $this->accountId => Http::response([
                'status'  => 400,
                'success' => false,
                'code'    => 'DUPLICATE_REFF_NO',
                'message' => 'Duplicate reference number',
            ], 400),
        ]);

        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 100000]);
        $bookingPayment = app(CreateBookingPayment::class);

        $customerData = [
            'namapelanggan' => 'Error Test',
            'email'         => 'error@example.com',
            'nomorhp'       => '081298765432',
        ];

        $payment = $bookingPayment->createPendingPayment($this->tenant, 100000, $customerData);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SingaPay API Error: Duplicate reference number');

        $bookingPayment->generatePaymentLink($payment, $service, $customerData);
    }

    public function test_payment_link_creation_401_error_handling(): void
    {
        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => ['access_token' => 'mocked-jwt-token', 'expires_in' => 3600],
            ], 200),
            'https://payment-b2b.singapay.id/api/v1.0/payment-link-manage/' . $this->accountId => Http::response([
                'status'  => 401,
                'success' => false,
                'message' => 'Unauthorized partner request',
            ], 401),
        ]);

        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 100000]);
        $bookingPayment = app(CreateBookingPayment::class);

        $payment = $bookingPayment->createPendingPayment($this->tenant, 100000, [
            'namapelanggan' => '401 Test',
            'email'         => 't401@example.com',
            'nomorhp'       => '081298765432',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SingaPay API Error: Unauthorized partner request');

        $bookingPayment->generatePaymentLink($payment, $service, ['namapelanggan' => '401 Test']);
    }

    public function test_payment_link_creation_422_error_handling(): void
    {
        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => ['access_token' => 'mocked-jwt-token', 'expires_in' => 3600],
            ], 200),
            'https://payment-b2b.singapay.id/api/v1.0/payment-link-manage/' . $this->accountId => Http::response([
                'status'  => 422,
                'success' => false,
                'message' => 'Invalid amount format',
            ], 422),
        ]);

        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 100000]);
        $bookingPayment = app(CreateBookingPayment::class);

        $payment = $bookingPayment->createPendingPayment($this->tenant, 100000, [
            'namapelanggan' => '422 Test',
            'email'         => 't422@example.com',
            'nomorhp'       => '081298765432',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SingaPay API Error: Invalid amount format');

        $bookingPayment->generatePaymentLink($payment, $service, ['namapelanggan' => '422 Test']);
    }

    public function test_payment_link_creation_500_error_handling(): void
    {
        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => ['access_token' => 'mocked-jwt-token', 'expires_in' => 3600],
            ], 200),
            'https://payment-b2b.singapay.id/api/v1.0/payment-link-manage/' . $this->accountId => Http::response([
                'status'  => 500,
                'success' => false,
                'message' => 'Internal server error from SingaPay',
            ], 500),
        ]);

        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 100000]);
        $bookingPayment = app(CreateBookingPayment::class);

        $payment = $bookingPayment->createPendingPayment($this->tenant, 100000, [
            'namapelanggan' => '500 Test',
            'email'         => 't500@example.com',
            'nomorhp'       => '081298765432',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SingaPay API Error: Internal server error from SingaPay');

        $bookingPayment->generatePaymentLink($payment, $service, ['namapelanggan' => '500 Test']);
    }

    public function test_payment_link_creation_missing_payment_url_throws_exception(): void
    {
        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => ['access_token' => 'mocked-jwt-token', 'expires_in' => 3600],
            ], 200),
            'https://payment-b2b.singapay.id/api/v1.0/payment-link-manage/' . $this->accountId => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => [
                    'id'          => 9999,
                    'reff_no'     => 'BKG-NO-URL',
                    'payment_url' => '', // Missing URL
                ],
            ], 200),
        ]);

        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 100000]);
        $bookingPayment = app(CreateBookingPayment::class);

        $payment = $bookingPayment->createPendingPayment($this->tenant, 100000, [
            'namapelanggan' => 'No URL Test',
            'email'         => 'nourl@example.com',
            'nomorhp'       => '081298765432',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Payment gateway did not return a valid payment link URL.');

        $bookingPayment->generatePaymentLink($payment, $service, ['namapelanggan' => 'No URL Test']);
    }

    public function test_account_resolution_fallback_rejects_ambiguity(): void
    {
        Config::set('services.singapay.account_id', null);

        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => ['access_token' => 'mocked-jwt-token', 'expires_in' => 3600],
            ], 200),
            'https://payment-b2b.singapay.id/api/v1.0/accounts' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => [
                    ['id' => 'ACC_01', 'status' => 'active'],
                    ['id' => 'ACC_02', 'status' => 'active'],
                ],
            ], 200),
        ]);

        /** @var SingaPayClient $client */
        $client = app(SingaPayClient::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Multiple active SingaPay accounts found');

        $client->resolveAccountId();
    }

    public function test_account_resolution_fallback_succeeds_with_single_active_account(): void
    {
        Config::set('services.singapay.account_id', null);

        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => ['access_token' => 'mocked-jwt-token', 'expires_in' => 3600],
            ], 200),
            'https://payment-b2b.singapay.id/api/v1.0/accounts' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => [
                    ['id' => 'ACC_SINGLE_ACTIVE', 'status' => 'active'],
                ],
            ], 200),
        ]);

        /** @var SingaPayClient $client */
        $client = app(SingaPayClient::class);
        $resolved = $client->resolveAccountId();

        $this->assertEquals('ACC_SINGLE_ACTIVE', $resolved);
    }

    public function test_account_resolution_fallback_rejects_when_no_active_account(): void
    {
        Config::set('services.singapay.account_id', null);

        Http::fake([
            'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => ['access_token' => 'mocked-jwt-token', 'expires_in' => 3600],
            ], 200),
            'https://payment-b2b.singapay.id/api/v1.0/accounts' => Http::response([
                'status'  => 200,
                'success' => true,
                'data'    => [
                    ['id' => 'ACC_INACTIVE', 'status' => 'inactive'],
                ],
            ], 200),
        ]);

        /** @var SingaPayClient $client */
        $client = app(SingaPayClient::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No active SingaPay account found for this merchant.');

        $client->resolveAccountId();
    }

    // ==========================================
    // 3. WEBHOOK STATUS MAPPINGS
    // ==========================================

    public function test_webhook_paid_status(): void
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
            'order_id'       => 'BKG-PAID-001',
            'payment_url'    => 'https://payment-link.singapay.id/b2b/BKG-PAID-001',
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

    public function test_webhook_pending_status(): void
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
            'payment_url'    => 'https://payment-link.singapay.id/b2b/BKG-PENDING-001',
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

    public function test_webhook_expired_status(): void
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
            'payment_url'    => 'https://payment-link.singapay.id/b2b/BKG-EXPIRED-001',
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

    public function test_webhook_failed_status(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 75000]);
        $schedule = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '17:00:00',
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 75000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-FAILED-001',
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

        $payload = $this->makeWebhookPayload($payment->order_id, 75000, 'failed');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(200);

        $payment->refresh();
        $booking->refresh();

        $this->assertEquals(PaymentState::STATUS_GAGAL, $payment->status);
        $this->assertEquals('cancelled', $booking->status);
    }

    // ==========================================
    // 4. WEBHOOK SECURITY & REPLAY PROTECTION
    // ==========================================

    public function test_webhook_with_client_secret_accepted(): void
    {
        $payload = $this->makeWebhookPayload('NONEXISTENT-AUTH', 50000, 'paid');

        // Signed with official client_secret
        $response = $this->postSingaPayWebhook(
            '/api/webhooks/singapay/transaction',
            $payload,
            $this->clientSecret
        );

        // Security check passed (returns 404 because payment not in db, not 401)
        $this->assertNotEquals(401, $response->status());
        $response->assertStatus(404);
    }

    public function test_webhook_with_hmac_validation_key_fallback_accepted(): void
    {
        $payload = $this->makeWebhookPayload('NONEXISTENT-AUTH', 50000, 'paid');

        // Signed with hmac_validation_key for backwards compatibility
        $response = $this->postSingaPayWebhook(
            '/api/webhooks/singapay/transaction',
            $payload,
            $this->hmacKey
        );

        $this->assertNotEquals(401, $response->status());
        $response->assertStatus(404);
    }

    public function test_invalid_hmac_rejected(): void
    {
        $payload = $this->makeWebhookPayload('BKG-INVALID-HMAC', 50000, 'paid');

        $response = $this->postSingaPayWebhook(
            '/api/webhooks/singapay/transaction',
            $payload,
            'wrong_secret_key'
        );

        $response->assertStatus(401);
        $response->assertJson(['status' => 'error', 'message' => 'Invalid signature']);
    }

    public function test_missing_signature_header_rejected(): void
    {
        $payload = $this->makeWebhookPayload('BKG-NO-SIG', 50000, 'paid');

        $response = $this->withHeaders([
            'X-Timestamp'   => (string) time(),
            'Authorization' => 'Bearer token123',
            'Content-Type'  => 'application/json',
        ])->postJson('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(401);
        $response->assertJson(['status' => 'error', 'message' => 'Invalid signature']);
    }

    public function test_expired_timestamp_rejected_as_replay_attack(): void
    {
        $payload = $this->makeWebhookPayload('BKG-EXPIRED-TS', 50000, 'paid');

        // Timestamp from 10 minutes ago (> 300 seconds tolerance)
        $oldTimestamp = (string) (time() - 600);

        $response = $this->postSingaPayWebhook(
            '/api/webhooks/singapay/transaction',
            $payload,
            $this->clientSecret,
            'dummy_jwt_token',
            $oldTimestamp
        );

        $response->assertStatus(401);
        $response->assertJson(['status' => 'error', 'message' => 'Invalid or expired timestamp']);
    }

    public function test_future_timestamp_rejected_as_replay_attack(): void
    {
        $payload = $this->makeWebhookPayload('BKG-FUTURE-TS', 50000, 'paid');

        // Timestamp 10 minutes in the future (> 300 seconds tolerance)
        $futureTimestamp = (string) (time() + 600);

        $response = $this->postSingaPayWebhook(
            '/api/webhooks/singapay/transaction',
            $payload,
            $this->clientSecret,
            'dummy_jwt_token',
            $futureTimestamp
        );

        $response->assertStatus(401);
        $response->assertJson(['status' => 'error', 'message' => 'Invalid or expired timestamp']);
    }

    public function test_malformed_json_body_rejected(): void
    {
        $response = $this->withHeaders([
            'X-Signature'   => 'dummy_signature',
            'X-Timestamp'   => (string) time(),
            'Authorization' => 'Bearer token123',
            'Content-Type'  => 'application/json',
        ])->call(
            'POST',
            '/api/webhooks/singapay/transaction',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid-json-body:::'
        );

        $response->assertStatus(400);
        $response->assertJson(['status' => 'error', 'message' => 'Malformed JSON body']);
    }

    public function test_malformed_signature_rejected(): void
    {
        $payload = $this->makeWebhookPayload('BKG-MALFORMED-SIG', 50000, 'paid');

        $response = $this->withHeaders([
            'X-Signature'   => '!!!malformed-signature-non-hex!!!',
            'X-Timestamp'   => (string) time(),
            'Authorization' => 'Bearer token123',
            'Content-Type'  => 'application/json',
        ])->postJson('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(401);
        $response->assertJson(['status' => 'error', 'message' => 'Invalid signature']);
    }

    public function test_invalid_authorization_token_rejected(): void
    {
        $payload = $this->makeWebhookPayload('BKG-INVALID-AUTH', 50000, 'paid');
        $timestamp = (string) time();

        /** @var SingaPayWebhookVerifier $verifier */
        $verifier = app(SingaPayWebhookVerifier::class);

        // Signature is computed with 'expected_token'
        $signature = $verifier->computeSignature(
            '/api/webhooks/singapay/transaction',
            'expected_token',
            $payload,
            $timestamp,
            $this->clientSecret
        );

        // But incoming header sends different token 'tampered_token'
        $response = $this->withHeaders([
            'X-Signature'   => $signature,
            'X-Timestamp'   => $timestamp,
            'Authorization' => 'Bearer tampered_token',
            'Content-Type'  => 'application/json',
        ])->postJson('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(401);
        $response->assertJson(['status' => 'error', 'message' => 'Invalid signature']);
    }

    // ==========================================
    // 5. IDEMPOTENCY & STATE MACHINE TESTS
    // ==========================================

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

        // First delivery: pending -> sukses
        $res1 = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);
        $res1->assertStatus(200);

        $payment->refresh();
        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->status);

        // Second delivery (duplicate retry): sukses -> sukses (idempotent 200)
        $res2 = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);
        $res2->assertStatus(200);
        $res2->assertJson(['status' => 'success']);

        $payment->refresh();
        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->status);
        $this->assertEquals(1, Booking::withoutGlobalScopes()->where('idpayment', $payment->id)->count());
    }

    public function test_immutable_successful_payment_cannot_transition_to_failed(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 50000]);
        $schedule = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '18:00:00',
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_SUKSES, // Already success
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-IMMUTABLE-001',
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
            'jam'            => '18:00:00',
            'status'         => 'paid',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        // Malicious or delayed expired webhook arrives for already paid payment
        $payload = $this->makeWebhookPayload($payment->order_id, 50000, 'expired');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);
        $response->assertStatus(200);

        $payment->refresh();
        $booking->refresh();

        // Payment and booking remain sukses / paid
        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->status);
        $this->assertEquals('paid', $booking->status);
    }

    // ==========================================
    // 6. MULTI-BOOKING (MULTI-SLOT) PAYMENT TESTS
    // ==========================================

    public function test_multi_slot_booking_webhook_settles_all_associated_bookings(): void
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

    // ==========================================
    // 7. AMOUNT VALIDATION & ISOLATION TESTS
    // ==========================================

    public function test_amount_mismatch_rejected(): void
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

        // Incoming payload contains manipulated amount (10000 instead of 100000)
        $payload = $this->makeWebhookPayload($payment->order_id, 10000, 'paid');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error', 'message' => 'Transaction amount mismatch']);

        $payment->refresh();
        $this->assertEquals(PaymentState::STATUS_PENDING, $payment->status);
    }

    public function test_webhook_paid_followed_by_failed_remains_sukses(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 60000]);
        $schedule = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '19:00:00',
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 60000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-PAID-THEN-FAIL',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Pelanggan PaidThenFail',
            'email_pembayar' => 'paidfail@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $booking = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Pelanggan PaidThenFail',
            'nomorhp'        => '08123456789',
            'email'          => 'paidfail@test.com',
            'tanggalbooking' => $schedule->tanggal,
            'jam'            => '19:00:00',
            'status'         => 'pending',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        // 1. Paid webhook arrives
        $resPaid = $this->postSingaPayWebhook(
            '/api/webhooks/singapay/transaction',
            $this->makeWebhookPayload($payment->order_id, 60000, 'paid')
        );
        $resPaid->assertStatus(200);

        $payment->refresh();
        $booking->refresh();
        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->status);
        $this->assertEquals('paid', $booking->status);

        // 2. Delayed failed webhook arrives
        $resFail = $this->postSingaPayWebhook(
            '/api/webhooks/singapay/transaction',
            $this->makeWebhookPayload($payment->order_id, 60000, 'failed')
        );
        $resFail->assertStatus(200);

        $payment->refresh();
        $booking->refresh();
        // Payment and booking MUST remain sukses / paid
        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->status);
        $this->assertEquals('paid', $booking->status);
    }

    public function test_multi_slot_booking_does_not_affect_unrelated_bookings(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 50000]);
        $scheduleA = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '09:00:00',
        ]);
        $scheduleB = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->addDay()->toDateString(),
            'jam_mulai' => '10:00:00',
        ]);

        // Payment A (being paid)
        $paymentA = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-UNRELATED-PAY-A',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'User A',
            'email_pembayar' => 'a@test.com',
            'hp_pembayar'    => '08123456789',
        ]);
        $bookingA = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $scheduleA->id,
            'idpayment'      => $paymentA->id,
            'namapelanggan'  => 'User A',
            'nomorhp'        => '08123456789',
            'email'          => 'a@test.com',
            'tanggalbooking' => $scheduleA->tanggal,
            'jam'            => '09:00:00',
            'status'         => 'pending',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        // Payment B (unrelated pending booking)
        $paymentB = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-UNRELATED-PAY-B',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'User B',
            'email_pembayar' => 'b@test.com',
            'hp_pembayar'    => '08123456789',
        ]);
        $bookingB = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $scheduleB->id,
            'idpayment'      => $paymentB->id,
            'namapelanggan'  => 'User B',
            'nomorhp'        => '08123456789',
            'email'          => 'b@test.com',
            'tanggalbooking' => $scheduleB->tanggal,
            'jam'            => '10:00:00',
            'status'         => 'pending',
            'booking_code'   => Booking::generateBookingCode(),
        ]);

        $res = $this->postSingaPayWebhook(
            '/api/webhooks/singapay/transaction',
            $this->makeWebhookPayload($paymentA->order_id, 50000, 'paid')
        );
        $res->assertStatus(200);

        $bookingA->refresh();
        $bookingB->refresh();
        $paymentB->refresh();

        // Booking A must be paid
        $this->assertEquals('paid', $bookingA->status);

        // Unrelated Booking B and Payment B MUST remain pending!
        $this->assertEquals('pending', $bookingB->status);
        $this->assertEquals(PaymentState::STATUS_PENDING, $paymentB->status);
    }

    public function test_webhook_currency_mismatch_rejected(): void
    {
        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 100000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-CURRENCY-MISMATCH',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Currency Test',
            'email_pembayar' => 'curr@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $payload = $this->makeWebhookPayload($payment->order_id, 100000, 'paid');
        // Manipulate currency to non-IDR
        $payload['data']['transaction']['amount']['currency'] = 'USD';

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error', 'message' => 'Transaction currency mismatch']);

        $payment->refresh();
        $this->assertEquals(PaymentState::STATUS_PENDING, $payment->status);
    }

    public function test_unknown_order_id_returns_404(): void
    {
        $payload = $this->makeWebhookPayload('NONEXISTENT-ORDER-ID', 100000, 'paid');

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(404);
        $response->assertJson(['status' => 'error', 'message' => 'Payment not found']);
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

        $this->assertFalse(app(TenantContext::class)->hasTenant());
    }
}
