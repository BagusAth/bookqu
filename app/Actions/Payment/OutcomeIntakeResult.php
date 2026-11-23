<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Models\Payment;

/**
 * Answer of the Outcome intake: whether the provider outcome was accepted, why not,
 * and the payment's stored status afterwards.
 */
final readonly class OutcomeIntakeResult
{
    public const REJECTED_PAYMENT_NOT_FOUND = 'payment-not-found';

    public const REJECTED_PROVIDER_MISMATCH = 'provider-mismatch';

    public const REJECTED_TENANT_INVALID = 'tenant-invalid';

    public const REJECTED_AMOUNT_MISSING = 'amount-or-currency-missing';

    public const REJECTED_AMOUNT_MISMATCH = 'amount-mismatch';

    public const REJECTED_CURRENCY_MISMATCH = 'currency-mismatch';

    public function __construct(
        public bool $accepted,
        public ?Payment $payment,
        public ?string $rejection = null,
        public ?PaymentTransitionResult $transition = null,
    ) {}

    public static function rejected(string $rejection, ?Payment $payment = null): self
    {
        return new self(false, $payment, $rejection);
    }

    /**
     * The payment's stored status after the outcome, or null when no payment was found.
     */
    public function status(): ?string
    {
        return $this->payment?->status;
    }
}
