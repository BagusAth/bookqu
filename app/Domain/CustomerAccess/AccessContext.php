<?php

namespace App\Domain\CustomerAccess;

use App\Models\Booking;
use App\Models\CustomerAccessGrant;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

final readonly class AccessContext
{
    /** @param Collection<int, Booking> $bookings */
    public function __construct(
        public CustomerAccessGrant $grant,
        public ?Payment $payment,
        public Collection $bookings,
    ) {}

    public function allows(AccessOperation $operation, ?Payment $payment = null, ?Booking $booking = null): bool
    {
        if (! AccessRules::permits($this->grant->profile, $operation)) {
            return false;
        }
        if ($payment !== null && ($this->payment === null || (int) $payment->id !== (int) $this->payment->id
            || (int) $payment->idtenant !== (int) $this->grant->idtenant)) {
            return false;
        }
        if ($booking !== null && ((int) $booking->idtenant !== (int) $this->grant->idtenant
            || ! $this->bookings->contains('id', $booking->id))) {
            return false;
        }

        return true;
    }
}
