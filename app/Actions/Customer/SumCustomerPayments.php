<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Domain\Booking\BookingState;
use App\Domain\Customer\CustomerIdentity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * What each customer has paid (owner-portal.md §3 rule 8, AC-PLG-02): the amounts of successful
 * booking payments, each payment counted once, for bookings that were not cancelled or left unpaid.
 */
class SumCustomerPayments
{
    /** Booking states whose payment counts as spent by the customer. */
    public const COUNTED_STATES = [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED, 'no_show'];

    /**
     * @param  list<string>  $identifiers  normalized customer identities
     * @return array<string, float> identity => amount paid
     */
    public function execute(int $tenantId, array $identifiers): array
    {
        if ($identifiers === []) {
            return [];
        }

        return $this->query($tenantId, $identifiers)->pluck('total_spent', 'identifier')
            ->map(fn ($spent): float => (float) $spent)->all();
    }

    /** @param list<string>|null $identifiers */
    public function query(int $tenantId, ?array $identifiers = null): Builder
    {
        $identity = CustomerIdentity::sql('bookings');

        $rows = DB::table('bookings')
            ->join('payments', function ($join): void {
                $join->on('bookings.idpayment', '=', 'payments.id')
                    ->orOn('payments.idbooking', '=', 'bookings.id');
            })
            ->where('bookings.idtenant', $tenantId)
            ->where('payments.idtenant', $tenantId)
            ->where('payments.tipe', 'booking')
            ->where('payments.status', 'sukses')
            ->whereIn('bookings.status', self::COUNTED_STATES)
            ->when($identifiers !== null, fn (Builder $query) => $query->whereIn(DB::raw($identity), $identifiers))
            ->select([DB::raw($identity.' AS identifier'), 'payments.id AS payment_id', 'payments.jumlah AS amount'])
            ->distinct();

        return DB::query()->fromSub($rows, 'paid_once')
            ->select('identifier')->selectRaw('SUM(amount) AS total_spent')->groupBy('identifier');
    }
}
