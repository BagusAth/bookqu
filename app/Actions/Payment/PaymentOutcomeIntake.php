<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Domain\Payment\ProviderOutcome;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

/**
 * The Outcome intake (ADR-0007): every provider outcome — webhook or server-side
 * status query — is validated here once and handed to the Payment lifecycle.
 * The intake never writes payment state itself.
 */
class PaymentOutcomeIntake
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected PaymentLifecycle $lifecycle,
    ) {}

    /**
     * Validate an authenticated provider outcome and apply it.
     */
    public function receive(ProviderOutcome $outcome): OutcomeIntakeResult
    {
        $payment = $this->find($outcome->references);

        if ($payment === null) {
            Log::warning('PaymentOutcomeIntake: payment not found', [
                'provider' => $outcome->provider,
                'references' => $outcome->references,
            ]);

            return OutcomeIntakeResult::rejected(OutcomeIntakeResult::REJECTED_PAYMENT_NOT_FOUND);
        }

        $rejection = $this->validate($payment, $outcome);

        if ($rejection !== null) {
            Log::critical('PaymentOutcomeIntake: provider outcome rejected', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'provider' => $outcome->provider,
                'rejection' => $rejection,
                'result' => $outcome->result,
                'transaction_id' => $outcome->transactionId,
            ]);

            return OutcomeIntakeResult::rejected($rejection, $payment);
        }

        Log::info('PaymentOutcomeIntake: provider outcome accepted', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'provider' => $outcome->provider,
            'result' => $outcome->result,
            'transaction_id' => $outcome->transactionId,
            'event_time' => $outcome->eventTime,
        ]);

        $transition = match ($outcome->result) {
            PaymentState::STATUS_SUKSES => $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded, $outcome->method),
            PaymentState::STATUS_GAGAL => $this->lifecycle->transition($payment, PaymentTransition::ProviderFailed, $outcome->method),
            default => null,
        };

        return new OutcomeIntakeResult(true, $transition->payment ?? $payment->fresh(), null, $transition);
    }

    /**
     * Ask the payment's own provider for its current status and apply the answer
     * (ADR-0007 decisions 4–5). A successful payment cannot change any more, and
     * manual payments (free, cash) have no provider to ask.
     */
    public function refresh(Payment $payment): OutcomeIntakeResult
    {
        $payment = $payment->fresh() ?? $payment;
        $provider = PaymentRules::providerOf($payment);

        if ($payment->status === PaymentState::STATUS_SUKSES || ! in_array($provider, [PaymentState::METODE_SINGAPAY, PaymentState::METODE_MIDTRANS], true)) {
            return new OutcomeIntakeResult(true, $payment);
        }

        $outcome = $this->gateways->driver($provider)->queryOutcome($payment);

        if (! $outcome->isFinal()) {
            return new OutcomeIntakeResult(true, $payment);
        }

        return $this->receive($outcome);
    }

    /**
     * Status check for the payer or owner: ask the provider first, then expire the
     * payment if it is still pending past its deadline (ADR-0007 decision 5).
     */
    public function check(Payment $payment): OutcomeIntakeResult
    {
        $result = $this->refresh($payment);
        $current = $result->payment ?? $payment;

        if ($current->status === PaymentState::STATUS_PENDING && $current->isExpired()) {
            $expiry = $this->lifecycle->transition($current, PaymentTransition::Expired);

            return new OutcomeIntakeResult(true, $expiry->payment, null, $expiry);
        }

        return $result;
    }

    private function validate(Payment $payment, ProviderOutcome $outcome): ?string
    {
        if (PaymentRules::providerOf($payment) !== strtolower($outcome->provider)) {
            return OutcomeIntakeResult::REJECTED_PROVIDER_MISMATCH;
        }

        // scope-bypass: the tenant is the payment's stored tenant; webhooks carry no tenant context.
        if (! $payment->idtenant || ! Tenant::withoutGlobalScopes()->whereKey($payment->idtenant)->exists()) {
            return OutcomeIntakeResult::REJECTED_TENANT_INVALID;
        }

        if ($outcome->result === PaymentState::STATUS_SUKSES && ($outcome->amount === null || $outcome->currency === null)) {
            return OutcomeIntakeResult::REJECTED_AMOUNT_MISSING;
        }

        if ($outcome->currency !== null && strtoupper(trim($outcome->currency)) !== 'IDR') {
            return OutcomeIntakeResult::REJECTED_CURRENCY_MISMATCH;
        }

        if ($outcome->amount !== null && ! PaymentRules::amountMatches($payment, $outcome->amount)) {
            return OutcomeIntakeResult::REJECTED_AMOUNT_MISMATCH;
        }

        return null;
    }

    /**
     * @param  list<string>  $references
     */
    private function find(array $references): ?Payment
    {
        foreach (array_values(array_unique(array_filter($references, fn ($reference) => trim($reference) !== ''))) as $reference) {
            foreach (['order_id', 'external_id'] as $column) {
                // scope-bypass: provider outcomes arrive without tenant context; ownership is validated next.
                $payment = Payment::withoutGlobalScope(TenantScope::class)->where($column, $reference)->first();

                if ($payment !== null) {
                    return $payment;
                }
            }
        }

        return null;
    }
}
