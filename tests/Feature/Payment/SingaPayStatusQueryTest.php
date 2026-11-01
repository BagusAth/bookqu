<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Actions\Payment\PaymentOutcomeIntake;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\ProviderOutcome;
use App\Infrastructure\Payments\SingaPay\SingaPayPaymentGateway;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * SingaPay status query (BQ-074): a payment link's use count and its paid history,
 * recorded from the sandbox API on 2026-11-01 with personal data removed.
 */
class SingaPayStatusQueryTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://payment-b2b.singapay.id';

    private const ACCOUNT = '01JTESTACCOUNTULID00000000000';

    private const LINK_ID = '4328';

    private const ORDER_ID = 'BKG-1-1793535530-174';

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();

        Config::set('services.singapay.base_url', self::BASE);
        Config::set('services.singapay.client_id', 'test_client_id');
        Config::set('services.singapay.client_secret', 'test_secret_key_1234567890');
        Config::set('services.singapay.api_key', 'test_api_key_abc');
        Config::set('services.singapay.account_id', self::ACCOUNT);

        $this->tenant = Tenant::factory()->create(['payment_mode' => 'platform']);
        app(TenantContext::class)->setTenantId($this->tenant->id);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        parent::tearDown();
    }

    public function test_a_paid_history_settles_the_pending_booking_group(): void
    {
        [$payment, $booking] = $this->pendingBooking();
        $this->fakeSingaPay(usage: 1, histories: [
            $this->history('BKG-207-1793518255-310', '150000.00'),
            $this->history(self::ORDER_ID, '20000.00'),
        ]);

        app(PaymentOutcomeIntake::class)->check($payment);

        $this->assertSame(PaymentState::STATUS_SUKSES, $payment->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->status);
        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), self::BASE.'/api/v1.0/payment-link-histories/'.self::ACCOUNT)
            && $request['page'] === 1 && $request['per_page'] === 100
            && ($request->header('X-PARTNER-ID')[0] ?? null) === 'test_api_key_abc'
            && ($request->header('Authorization')[0] ?? null) === 'Bearer token');
    }

    public function test_an_unused_link_is_unknown_and_spares_the_provider_between_polls(): void
    {
        [$payment, $booking] = $this->pendingBooking();
        $this->fakeSingaPay(usage: 0, histories: []);
        $gateway = app(SingaPayPaymentGateway::class);

        $this->assertSame(ProviderOutcome::RESULT_UNKNOWN, $gateway->queryOutcome($payment)->result);
        $this->assertSame(ProviderOutcome::RESULT_UNKNOWN, $gateway->queryOutcome($payment)->result);

        Http::assertSentCount(2); // token + one link read; the second poll is answered from the throttle
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'payment-link-histories'));
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_a_paid_history_with_another_amount_is_rejected(): void
    {
        [$payment, $booking] = $this->pendingBooking();
        $this->fakeSingaPay(usage: 1, histories: [$this->history(self::ORDER_ID, '1000.00')]);

        $result = app(PaymentOutcomeIntake::class)->check($payment);

        $this->assertFalse($result->accepted);
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_a_link_of_another_order_or_without_paid_history_is_unknown(): void
    {
        [$payment] = $this->pendingBooking();
        $gateway = app(SingaPayPaymentGateway::class);

        $this->fakeSingaPay(usage: 1, histories: [$this->history(self::ORDER_ID, '20000.00')], linkReff: 'BKG-OTHER');
        $this->assertSame(ProviderOutcome::RESULT_UNKNOWN, $gateway->queryOutcome($payment)->result);

        $this->fakeSingaPay(usage: 1, histories: [$this->history('BKG-OTHER', '20000.00')]);
        $this->assertSame(ProviderOutcome::RESULT_UNKNOWN, $gateway->queryOutcome($payment)->result);
    }

    public function test_provider_failure_or_a_missing_link_id_is_unknown(): void
    {
        [$payment] = $this->pendingBooking();
        Http::fake([
            self::BASE.'/api/v1.1/access-token/b2b' => Http::response(['data' => ['access_token' => 'token']]),
            self::BASE.'/api/v1.0/payment-link-manage/*' => Http::response(['error' => ['code' => 500]], 500),
        ]);
        $gateway = app(SingaPayPaymentGateway::class);

        $this->assertSame(ProviderOutcome::RESULT_UNKNOWN, $gateway->queryOutcome($payment)->result);

        $payment->forceFill(['external_id' => self::ORDER_ID])->save();
        $this->assertSame(ProviderOutcome::RESULT_UNKNOWN, $gateway->queryOutcome($payment->fresh())->result);
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
    }

    /**
     * @return array{0: Payment, 1: Booking}
     */
    private function pendingBooking(): array
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 20000]);
        $schedule = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '10:00:00',
        ]);
        $payment = Payment::create([
            'idtenant' => $this->tenant->id,
            'tipe' => PaymentState::TIPE_BOOKING,
            'jumlah' => 20000,
            'status' => PaymentState::STATUS_PENDING,
            'metode' => PaymentState::METODE_SINGAPAY,
            'provider' => 'singapay',
            'order_id' => self::ORDER_ID,
            'external_id' => self::LINK_ID,
            'expired_at' => now()->addMinutes(15),
            'nama_pembayar' => 'Pelanggan',
            'email_pembayar' => 'pelanggan@example.test',
            'hp_pembayar' => '081234567890',
        ]);
        $booking = Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $service->id,
            'idschedule' => $schedule->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Pelanggan',
            'nomorhp' => '081234567890',
            'email' => 'pelanggan@example.test',
            'tanggalbooking' => $schedule->tanggal,
            'jam' => '10:00:00',
            'status' => 'pending',
            'booking_code' => Booking::generateBookingCode(),
        ]);

        return [$payment, $booking];
    }

    /**
     * @param  list<array<string, mixed>>  $histories
     */
    private function fakeSingaPay(int $usage, array $histories, string $linkReff = self::ORDER_ID): void
    {
        Http::fake([
            self::BASE.'/api/v1.1/access-token/b2b' => Http::response(['data' => ['access_token' => 'token']]),
            self::BASE.'/api/v1.0/payment-link-manage/'.self::ACCOUNT.'/'.self::LINK_ID => Http::response([
                'status' => 200,
                'success' => true,
                'data' => [
                    'id' => (int) self::LINK_ID,
                    'reff_no' => $linkReff,
                    'status' => $usage > 0 ? 'closed' : 'open',
                    'status_computed' => $usage > 0 ? 'closed' : 'open',
                    'is_expired' => false,
                    'max_usage' => 1,
                    'current_usage' => $usage,
                    'total_amount' => '20000.00',
                ],
            ]),
            self::BASE.'/api/v1.0/payment-link-histories/*' => Http::response([
                'status' => 200,
                'success' => true,
                'data' => $histories,
                'pagination' => ['count' => count($histories), 'total' => count($histories), 'perPage' => 100, 'currentPage' => 1, 'totalPages' => 1, 'links' => []],
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function history(string $orderId, string $amount): array
    {
        return [
            'id' => 2949,
            'reff_no' => '43289277720261101191930415pvv8NhGZ',
            'payment_link_reff_no' => $orderId,
            'amount' => $amount,
            'payment_date' => '2026-11-01T19:19:46+07:00',
            'status' => 'paid',
            'status_computed' => 'paid',
            'is_expired' => true,
        ];
    }
}
