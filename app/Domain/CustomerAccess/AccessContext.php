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

    public function authorize(AccessOperation $operation, ?Payment $payment = null, ?Booking $booking = null): void
    {
        abort_unless(AccessRules::permits($this->grant->profile, $operation), 404);
        if ($payment !== null) {
            abort_unless($this->payment !== null && (int) $payment->id === (int) $this->payment->id
                && (int) $payment->idtenant === (int) $this->grant->idtenant, 404);
        }
        if ($booking !== null) {
            abort_unless((int) $booking->idtenant === (int) $this->grant->idtenant
                && $this->bookings->contains('id', $booking->id), 404);
        }
    }
}
