<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Actions\Payment\Lifecycle\BookingGroupConsequences;
use App\Actions\Payment\Lifecycle\PaymentConsequences;
use App\Actions\Payment\Lifecycle\SubscriptionConsequences;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Domain\Payment\PaymentTransitionOutcome;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\PaymentRefundOwedOwnerNotification;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The Payment lifecycle module (ADR-0004): the only code that writes `payments.status`
 * and the only code that creates refunds.
 *
 * Every request runs in one transaction (joining the caller's when one is open), locks
 * the payment's tenant row and then the payment row (ADR-0005 decision 4), validates the
 * transition with PaymentState, and is idempotent. Consequences fan out through the
 * booking-group or subscription adapter; mail, notifications, cache invalidation and
 * gateway cancellation run after commit.
 */
class PaymentLifecycle
{
    public function __construct(
        protected BookingGroupConsequences $bookingGroup,
        protected SubscriptionConsequences $subscription,
        protected TenantContext $tenantContext,
    ) {}

    /**
     * Request a status transition for an existing payment.
     *
     * @param  string|null  $method  Payment method reported by the provider, or `cash` for owner settlement.
     */
    public function transition(Payment $payment, PaymentTransition $transition, ?string $method = null): PaymentTransitionResult
    {
        return $this->withinPaymentTenant($payment, function (?int $tenantId) use ($payment, $transition, $method): PaymentTransitionResult {
            $locked = $this->lock($payment, $tenantId);

            if ($locked === null) {
                return new PaymentTransitionResult(PaymentTransitionOutcome::Rejected, $payment, 'payment-not-found');
            }

            $consequences = $this->consequencesFor($locked);

            if (! $consequences->accepts($transition)) {
                return new PaymentTransitionResult(PaymentTransitionOutcome::Rejected, $locked, 'transition-not-applicable');
            }

            $target = $transition->targetStatus();

            if ($locked->status === $target) {
                return new PaymentTransitionResult(PaymentTransitionOutcome::AlreadyFinal, $locked);
            }

            if (PaymentState::isTerminal((string) $locked->status)) {
                if ($transition === PaymentTransition::ProviderSucceeded) {
                    return $this->refundOwed($locked, $consequences, 'late-success');
                }

                return new PaymentTransitionResult(PaymentTransitionOutcome::AlreadyFinal, $locked);
            }

            if (! PaymentState::canTransition((string) $locked->status, $target)) {
                return new PaymentTransitionResult(PaymentTransitionOutcome::Rejected, $locked, 'invalid-transition');
            }

            if ($target === PaymentState::STATUS_SUKSES && ! $consequences->canDeliver($locked)) {
                if ($transition === PaymentTransition::ProviderSucceeded) {
                    $this->fail($locked, $consequences, $transition, $method);

                    return $this->refundOwed($locked, $consequences, 'purchase-undeliverable');
                }

                return new PaymentTransitionResult(PaymentTransitionOutcome::Rejected, $locked, 'purchase-undeliverable');
            }

            if ($target === PaymentState::STATUS_SUKSES) {
                $locked->update([
                    'status' => PaymentState::STATUS_SUKSES,
                    'metode' => $method ?? $locked->metode ?? PaymentState::METODE_MIDTRANS,
                ]);
                $consequences->deliver($locked, $transition);
            } else {
                $this->fail($locked, $consequences, $transition, $method);
            }

            if ($transition->cancelsGatewayLink()) {
                DB::afterCommit(fn () => $this->requestGatewayCancellation($locked, $transition));
            }

            return new PaymentTransitionResult(PaymentTransitionOutcome::Changed, $locked);
        });
    }

    /**
     * Record a payment that is settled when it is created: free bookings (`gratis`) and
     * walk-ins recorded by the owner.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function recordSettled(array $attributes): Payment
    {
        return Payment::create(array_merge($attributes, ['status' => PaymentState::STATUS_SUKSES]));
    }

    /**
     * Record the refund owed when a customer cancels a booking whose payment succeeded.
     * At most one refund exists per payment (INV-PAY-06); returns null when none is owed.
     */
    public function refundCancelledBooking(Booking $booking, ?string $reason = null): ?Refund
    {
        if (! $booking->idpayment) {
            return null;
        }

        // scope-bypass: the payment is resolved by the booking's id; tenant equality is checked under lock.
        $payment = Payment::withoutGlobalScopes()->find($booking->idpayment);

        if ($payment === null) {
            return null;
        }

        return $this->withinPaymentTenant($payment, function (?int $tenantId) use ($payment, $booking, $reason): ?Refund {
            $locked = $this->lock($payment, $tenantId);

            if ($locked === null
                || $locked->status !== PaymentState::STATUS_SUKSES
                || (int) $locked->idtenant !== (int) $booking->idtenant
                || Refund::where('payment_id', $locked->id)->exists()) {
                return null;
            }

            return Refund::create([
                'booking_id' => $booking->id,
                'payment_id' => $locked->id,
                'jumlah' => $locked->jumlah,
                'status' => 'pending',
                'catatan' => $reason ?? 'Refund otomatis dari pembatalan booking oleh customer.',
            ]);
        });
    }

    private function fail(Payment $payment, PaymentConsequences $consequences, PaymentTransition $transition, ?string $method): void
    {
        $payment->update([
            'status' => PaymentState::STATUS_GAGAL,
            'metode' => $method ?? $payment->metode,
        ]);
        $consequences->withdraw($payment, $transition);
    }

    /**
     * Money arrived for a purchase that can no longer be delivered: the purchase stays
     * withdrawn, one refund is recorded and the owner and platform are alerted.
     */
    private function refundOwed(Payment $payment, PaymentConsequences $consequences, string $reason): PaymentTransitionResult
    {
        $refund = Refund::where('payment_id', $payment->id)->first();

        if ($refund !== null) {
            return new PaymentTransitionResult(PaymentTransitionOutcome::RefundOwed, $payment, $reason);
        }

        $refund = Refund::create([
            'booking_id' => $consequences->refundBookingId($payment),
            'payment_id' => $payment->id,
            'jumlah' => $payment->jumlah,
            'status' => 'pending',
            'catatan' => 'Pembayaran diterima setelah pesanan dibatalkan atau kedaluwarsa.',
        ]);

        DB::afterCommit(function () use ($payment, $refund, $reason) {
            Log::critical('PaymentLifecycle: payment succeeded after its purchase was withdrawn; refund owed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'refund_id' => $refund->id,
                'reason' => $reason,
            ]);

            try {
                // scope-bypass: alerts run after commit, outside any request tenant context.
                $owner = Tenant::withoutGlobalScopes()->with('user')->find($payment->idtenant)?->user;
                if ($owner instanceof User) {
                    $owner->notify(new PaymentRefundOwedOwnerNotification($payment, $refund));
                }
            } catch (\Throwable $e) {
                Log::error('PaymentLifecycle: refund-owed notification failed: '.$e->getMessage());
            }
        });

        return new PaymentTransitionResult(PaymentTransitionOutcome::RefundOwed, $payment, $reason);
    }

    /**
     * Gateway cancellation of the open payment link (ADR-0004 decision 9). Provider
     * implementations arrive with the provider-outcome work (BQ-002); until then this is
     * a logged no-op.
     */
    private function requestGatewayCancellation(Payment $payment, PaymentTransition $transition): void
    {
        Log::info('PaymentLifecycle: gateway cancellation requested (not implemented by providers yet, BQ-002)', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'provider' => $payment->provider,
            'transition' => $transition->value,
        ]);
    }

    private function consequencesFor(Payment $payment): PaymentConsequences
    {
        return $payment->tipe === PaymentState::TIPE_SUBSCRIPTION ? $this->subscription : $this->bookingGroup;
    }

    /**
     * Lock the tenant row, then lock and reload the payment.
     */
    private function lock(Payment $payment, ?int $tenantId): ?Payment
    {
        if ($tenantId !== null) {
            // scope-bypass: tenant-first lock on the payment's stored tenant (ADR-0005 decision 4).
            Tenant::withoutGlobalScopes()->lockForUpdate()->find($tenantId);
        }

        // scope-bypass: webhooks and commands have no tenant context; the tenant comes from the stored row.
        return Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);
    }

    /**
     * Run the work in one transaction with the tenant context set to the payment's
     * tenant, restoring the caller's context afterwards.
     *
     * @template TResult
     *
     * @param  callable(?int): TResult  $work  Receives the payment's tenant id as stored.
     * @return TResult
     */
    private function withinPaymentTenant(Payment $payment, callable $work): mixed
    {
        // scope-bypass: the stored tenant of the payment decides the lock and the context.
        $storedTenantId = Payment::withoutGlobalScopes()->whereKey($payment->id)->value('idtenant');
        $tenantId = $storedTenantId === null ? null : (int) $storedTenantId;

        $previousTenantId = $this->tenantContext->getTenantId();
        $this->tenantContext->setTenantId($tenantId);

        try {
            return DB::transaction(fn () => $work($tenantId));
        } finally {
            $this->tenantContext->setTenantId($previousTenantId);
        }
    }
}
