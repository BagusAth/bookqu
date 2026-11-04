<?php

namespace App\Domain\Booking;

/** The current checkout selection. Slot IDs and prices are rechecked before placement. */
final readonly class CheckoutDraft
{
    /** @param list<int|string|null>|int|string|null $scheduleIds */
    public function __construct(
        public int|string|null $tenantId,
        public int|string|null $serviceId,
        public ?string $date,
        public array|string|null $times,
        public int|string|null $scheduleId,
        public array|int|string|null $scheduleIds,
    ) {}
}
