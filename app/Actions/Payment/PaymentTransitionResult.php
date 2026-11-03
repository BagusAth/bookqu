<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentTransitionOutcome;
use App\Models\Payment;

/**
 * Typed answer of the Payment lifecycle module: the payment as stored after the
 * request and what happened to it.
 */
final readonly class PaymentTransitionResult
{
    public function __construct(
        public PaymentTransitionOutcome $outcome,
        public Payment $payment,
        public ?string $reason = null,
    ) {}

    /**
     * The payment's actual stored status after the request.
     */
    public function status(): string
    {
        return (string) $this->payment->status;
    }

    public function changed(): bool
    {
        return $this->outcome === PaymentTransitionOutcome::Changed;
    }

    public function rejected(): bool
    {
        return $this->outcome === PaymentTransitionOutcome::Rejected;
    }
}
