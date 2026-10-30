<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Actions\Payment\OutcomeIntakeResult;
use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Payment\PaymentOutcomeIntake;
use App\Contracts\Payments\PaymentGatewayInterface;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Domain\Payment\ProviderOutcome;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Refund;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The Outcome intake at its interface with a fake adapter at the gateway seam (ADR-0007, BQ-032).
 */
class PaymentOutcomeIntakeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private FakeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();

        $this->tenant = Tenant::factory()->create(['iduser' => User::factory()->create(['role' => 'owner'])->id]);
        $this->gateway = new FakeGateway;
        app(PaymentGatewayManager::class)->extend('midtrans', fn () => $this->gateway);
    }

    public function test_valid_success_is_applied_through_the_lifecycle(): void
    {
        [$payment, $booking] = $this->pendingBooking(amount: 150000);

        $result = $this->intake()->receive($this->outcome($payment, 'sukses', amount: '150000.00'));

        $this->assertTrue($result->accepted);
        $this->assertSame(PaymentState::STATUS_SUKSES, $payment->fresh()->status);
        $this->assertSame('qris', $payment->fresh()->metode);
        $this->assertSame('paid', $booking->fresh()->status);
    }

    public function test_repeated_success_changes_nothing_twice(): void
    {
        [$payment] = $this->pendingBooking();

        $this->intake()->receive($this->outcome($payment, 'sukses'));
        $second = $this->intake()->receive($this->outcome($payment, 'sukses'));

        $this->assertTrue($second->accepted);
        $this->assertSame(PaymentState::STATUS_SUKSES, $second->status());
        $this->assertSame(0, Refund::count());
    }

    /**
     * @return array<string, array{0: array<string, string|null>, 1: string}>
     */
    public static function rejectedOutcomes(): array
    {
        return [
            'another provider' => [['provider' => 'singapay'], OutcomeIntakeResult::REJECTED_PROVIDER_MISMATCH],
            'one rupiah short' => [['amount' => '99999'], OutcomeIntakeResult::REJECTED_AMOUNT_MISMATCH],
            'one sen over' => [['amount' => '100000.01'], OutcomeIntakeResult::REJECTED_AMOUNT_MISMATCH],
            'success without amount' => [['amount' => null], OutcomeIntakeResult::REJECTED_AMOUNT_MISSING],
            'success without currency' => [['currency' => null], OutcomeIntakeResult::REJECTED_AMOUNT_MISSING],
            'foreign currency' => [['currency' => 'USD'], OutcomeIntakeResult::REJECTED_CURRENCY_MISMATCH],
        ];
    }

    /**
     * @param  array<string, string|null>  $override
     */
    #[DataProvider('rejectedOutcomes')]
    public function test_invalid_outcomes_are_rejected_without_a_state_change(array $override, string $rejection): void
    {
        [$payment, $booking] = $this->pendingBooking();
        $outcome = $this->outcome($payment, 'sukses');

        $result = $this->intake()->receive(new ProviderOutcome(
            provider: array_key_exists('provider', $override) ? (string) $override['provider'] : $outcome->provider,
            references: $outcome->references,
            result: $outcome->result,
            amount: array_key_exists('amount', $override) ? $override['amount'] : $outcome->amount,
            currency: array_key_exists('currency', $override) ? $override['currency'] : $outcome->currency,
        ));

        $this->assertFalse($result->accepted);
        $this->assertSame($rejection, $result->rejection);
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_unknown_payment_is_rejected(): void
    {
        $result = $this->intake()->receive(new ProviderOutcome('midtrans', ['NO-SUCH-ORDER'], 'sukses', '1000', 'IDR'));

        $this->assertFalse($result->accepted);
        $this->assertSame(OutcomeIntakeResult::REJECTED_PAYMENT_NOT_FOUND, $result->rejection);
    }

    public function test_pending_and_unknown_outcomes_change_nothing(): void
    {
        [$payment] = $this->pendingBooking();

        foreach (['pending', ProviderOutcome::RESULT_UNKNOWN] as $state) {
            $result = $this->intake()->receive($this->outcome($payment, $state, amount: null, currency: null));

            $this->assertTrue($result->accepted);
            $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
        }
    }

    public function test_late_success_owes_one_refund(): void
    {
        [$payment, $booking] = $this->pendingBooking();
        app(PaymentLifecycle::class)->transition($payment, PaymentTransition::Expired);

        $this->intake()->receive($this->outcome($payment, 'sukses'));
        $this->intake()->receive($this->outcome($payment, 'sukses'));

        $this->assertSame(PaymentState::STATUS_GAGAL, $payment->fresh()->status);
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame(1, Refund::where('payment_id', $payment->id)->count());
    }

    public function test_refresh_applies_the_provider_answer(): void
    {
        [$payment] = $this->pendingBooking();
        $this->gateway->answer = $this->outcome($payment, 'sukses');

        $result = $this->intake()->refresh($payment);

        $this->assertSame(PaymentState::STATUS_SUKSES, $result->status());
        $this->assertSame(1, $this->gateway->queries);
    }

    public function test_unreachable_provider_never_fails_a_payment(): void
    {
        [$payment] = $this->pendingBooking();
        $this->gateway->answer = ProviderOutcome::unknown('midtrans', (string) $payment->order_id);

        $result = $this->intake()->refresh($payment);

        $this->assertSame(PaymentState::STATUS_PENDING, $result->status());
    }

    public function test_manual_payments_are_not_sent_to_a_provider(): void
    {
        $payment = app(PaymentLifecycle::class)->recordSettled([
            'idtenant' => $this->tenant->id,
            'tipe' => PaymentState::TIPE_BOOKING,
            'jumlah' => 0,
            'metode' => PaymentState::METODE_GRATIS,
            'provider' => 'manual',
            'order_id' => 'FREE-INTAKE-1',
        ]);

        $this->intake()->refresh($payment);

        $this->assertSame(0, $this->gateway->queries);
    }

    public function test_check_asks_the_provider_before_expiring(): void
    {
        [$paid] = $this->pendingBooking(expiredAt: now()->subMinute());
        $this->gateway->answer = $this->outcome($paid, 'sukses');

        $this->assertSame(PaymentState::STATUS_SUKSES, $this->intake()->check($paid)->status());

        [$abandoned] = $this->pendingBooking(expiredAt: now()->subMinute());
        $this->gateway->answer = ProviderOutcome::unknown('midtrans', (string) $abandoned->order_id);

        $this->assertSame(PaymentState::STATUS_GAGAL, $this->intake()->check($abandoned)->status());
    }

    public function test_payer_cancel_cancels_the_gateway_checkout_after_commit(): void
    {
        [$payment] = $this->pendingBooking();

        app(PaymentLifecycle::class)->transition($payment, PaymentTransition::CancelledByPayer);

        $this->assertSame([$payment->order_id], $this->gateway->cancelled);
    }

    public function test_failing_gateway_cancel_does_not_reverse_the_local_decision(): void
    {
        [$payment] = $this->pendingBooking();
        $this->gateway->failCancel = true;

        $result = app(PaymentLifecycle::class)->transition($payment, PaymentTransition::Expired);

        $this->assertTrue($result->changed());
        $this->assertSame(PaymentState::STATUS_GAGAL, $payment->fresh()->status);
    }

    public function test_subscription_payments_use_the_same_intake(): void
    {
        $plan = Plan::create(['namapaket' => 'pro', 'hargabulanan' => 150000, 'maxlayanan' => 10, 'maxbooking' => 100, 'isunlimited' => false]);
        $payment = Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'idplan' => $plan->id,
            'tipe' => PaymentState::TIPE_SUBSCRIPTION,
            'jumlah' => 150000,
            'provider' => 'midtrans',
        ]);

        $result = $this->intake()->receive($this->outcome($payment, 'sukses', amount: '150000'));

        $this->assertSame(PaymentState::STATUS_SUKSES, $result->status());
    }

    private function intake(): PaymentOutcomeIntake
    {
        return app(PaymentOutcomeIntake::class);
    }

    private function outcome(Payment $payment, string $result, ?string $amount = '100000', ?string $currency = 'IDR'): ProviderOutcome
    {
        return new ProviderOutcome('midtrans', [(string) $payment->order_id], $result, $amount, $currency, 'TRX-1', 'qris');
    }

    /**
     * @return array{0: Payment, 1: Booking}
     */
    private function pendingBooking(float $amount = 100000, mixed $expiredAt = null): array
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => $amount]);
        $payment = Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'jumlah' => $amount,
            'provider' => 'midtrans',
            'expired_at' => $expiredAt ?? now()->addHour(),
        ]);
        $booking = Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $service->id,
            'idpayment' => $payment->id,
        ]);

        return [$payment, $booking];
    }
}

/**
 * Fake adapter at the gateway seam: answers status queries and records cancellations.
 */
class FakeGateway implements PaymentGatewayInterface
{
    public ?ProviderOutcome $answer = null;

    public int $queries = 0;

    /** @var list<string> */
    public array $cancelled = [];

    public bool $failCancel = false;

    public function getDriverName(): string
    {
        return 'midtrans';
    }

    public function createBookingPayment(Payment $payment, Service $service, array $customerData, int $slotCount = 1): array
    {
        return [];
    }

    public function createSubscriptionPayment(Payment $payment, Plan $plan, array $payerData, float $biayaPlatform = 0.0): array
    {
        return [];
    }

    public function queryOutcome(Payment $payment): ProviderOutcome
    {
        $this->queries++;

        return $this->answer ?? ProviderOutcome::unknown('midtrans', (string) $payment->order_id);
    }

    public function cancel(Payment $payment): void
    {
        if ($this->failCancel) {
            throw new \RuntimeException('Gateway unavailable');
        }

        $this->cancelled[] = (string) $payment->order_id;
    }
}
