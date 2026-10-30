<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Schedule\SlotRefusal;
use RuntimeException;

/**
 * A slot claim was refused. Thrown inside the caller's transaction so every mutation
 * of the operation, stale-pending cleanup included, rolls back (ADR-0005 decision 3).
 */
final class SlotClaimRefused extends RuntimeException
{
    public function __construct(
        public readonly SlotRefusal $refusal,
        public readonly ?int $scheduleId = null,
        public readonly string $slotTime = '',
    ) {
        parent::__construct($refusal->message($slotTime));
    }
}
