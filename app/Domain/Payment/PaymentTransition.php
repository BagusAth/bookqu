<?php

declare(strict_types=1);

namespace App\Domain\Payment;

/**
 * Why a payment changes state. Every terminal status write goes through the
 * Payment lifecycle module with one of these reasons (ADR-0004).
 */
enum PaymentTransition: string
{
    /** Verified webhook or server-side status query reports the money arrived. */
    case ProviderSucceeded = 'provider-succeeded';

    /** Verified webhook or status query reports the payment failed, was denied or cancelled. */
    case ProviderFailed = 'provider-failed';

    /** The payment deadline passed without a success. */
    case Expired = 'expired';

    /** The payer abandoned the checkout. */
    case CancelledByPayer = 'cancelled-by-payer';

    /** The gateway could not create the payment link or token. */
    case InitiationFailed = 'initiation-failed';

    /** The owner recorded the payment as received outside the gateway (whole payment group). */
    case SettledByOwner = 'settled-by-owner';

    public function targetStatus(): string
    {
        return match ($this) {
            self::ProviderSucceeded, self::SettledByOwner => PaymentState::STATUS_SUKSES,
            default => PaymentState::STATUS_GAGAL,
        };
    }

    /**
     * Whether an open gateway link must be cancelled after this transition (ADR-0004 decision 9).
     */
    public function cancelsGatewayLink(): bool
    {
        return in_array($this, [self::Expired, self::CancelledByPayer, self::SettledByOwner], true);
    }
}
