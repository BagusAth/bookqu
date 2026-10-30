<?php

declare(strict_types=1);

namespace App\Actions\Payment\Lifecycle;

use App\Actions\Subscription\ActivateSubscription;
use App\Domain\Payment\PaymentTransition;
use App\Models\Payment;

/**
 * Subscription adapter: a settled subscription payment activates or extends the plan.
 */
class SubscriptionConsequences implements PaymentConsequences
{
    public function __construct(
        protected ActivateSubscription $activateSubscription
    ) {}

    public function accepts(PaymentTransition $transition): bool
    {
        return $transition !== PaymentTransition::SettledByOwner;
    }

    public function canDeliver(Payment $payment): bool
    {
        return true;
    }

    public function deliver(Payment $payment, PaymentTransition $transition): void
    {
        $this->activateSubscription->execute($payment);
    }

    public function withdraw(Payment $payment, PaymentTransition $transition): void {}

    public function refundBookingId(Payment $payment): ?int
    {
        return null;
    }
}
