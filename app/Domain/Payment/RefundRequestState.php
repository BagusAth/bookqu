<?php

namespace App\Domain\Payment;

enum RefundRequestState: string
{
    case Open = 'open';
    case AcceptedFull = 'accepted_full';
    case AcceptedHalf = 'accepted_half';
    case Rejected = 'rejected';

    public function amount(float $paid): ?float
    {
        return match ($this) {
            self::AcceptedFull => $paid,
            self::AcceptedHalf => floor($paid / 2),
            default => null,
        };
    }
}
