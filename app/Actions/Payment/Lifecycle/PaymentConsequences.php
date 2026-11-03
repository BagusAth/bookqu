<?php

declare(strict_types=1);

namespace App\Actions\Payment\Lifecycle;

use App\Domain\Payment\PaymentTransition;
use App\Models\Payment;

/**
 * Internal seam of the Payment lifecycle module: what a payment's purpose does
 * when the payment settles or fails. Called inside the lifecycle transaction with
 * the tenant and payment rows locked; side effects outside the database run after commit.
 */
interface PaymentConsequences
{
    /**
     * Whether the purchase can still be delivered. A provider success for an
     * undeliverable purchase becomes a refund owed instead of a delivery.
     */
    public function canDeliver(Payment $payment): bool;

    /**
     * Whether this purpose accepts the given transition at all (for example, an
     * owner can settle a booking group but not a subscription).
     */
    public function accepts(PaymentTransition $transition): bool;

    /**
     * Deliver the purchase after the payment became `sukses`.
     */
    public function deliver(Payment $payment, PaymentTransition $transition): void;

    /**
     * Withdraw what the payment reserved after it became `gagal`.
     */
    public function withdraw(Payment $payment, PaymentTransition $transition): void;

    /**
     * The booking a refund for this payment is recorded against, if any.
     */
    public function refundBookingId(Payment $payment): ?int;
}
