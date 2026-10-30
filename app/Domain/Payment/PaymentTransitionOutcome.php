<?php

declare(strict_types=1);

namespace App\Domain\Payment;

/**
 * What a requested payment transition did (ADR-0004 decision 4).
 */
enum PaymentTransitionOutcome: string
{
    /** The payment moved to the requested status and its consequences were applied. */
    case Changed = 'changed';

    /** The payment was already final; nothing changed. */
    case AlreadyFinal = 'already-final';

    /** The request was refused; nothing changed. The result carries the reason. */
    case Rejected = 'rejected';

    /** Money arrived for a purchase that can no longer be delivered: one refund is owed and the owner was alerted. */
    case RefundOwed = 'refund-owed';
}
