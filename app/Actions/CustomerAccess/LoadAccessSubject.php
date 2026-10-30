<?php

namespace App\Actions\CustomerAccess;

use App\Domain\CustomerAccess\AccessContext;
use App\Models\Booking;
use App\Models\CustomerAccessGrant;
use App\Models\Payment;

final class LoadAccessSubject
{
    public function execute(CustomerAccessGrant $grant): AccessContext
    {
        $tenantId = (int) $grant->idtenant;
        abort_if(($grant->payment_id === null) === ($grant->booking_id === null), 404);
        $payment = null;
        if ($grant->payment_id !== null) {
            // scope-bypass: opaque grant lookup precedes tenant middleware; constrain by the stored grant tenant.
            $payment = Payment::withoutGlobalScopes()->where('idtenant', $tenantId)->find($grant->payment_id);
            abort_unless($payment !== null && $payment->tipe === 'booking', 404);
            // scope-bypass: inspect the entire bound group, including corrupt foreign members, before authorizing it.
            $bookings = Booking::withoutGlobalScopes()->where('idpayment', $payment->id)->orderBy('id')->get();
            $version = (int) $payment->customer_access_version;
        } else {
            // scope-bypass: standalone subject is constrained to the immutable grant tenant and booking id.
            $bookings = Booking::withoutGlobalScopes()->where('idtenant', $tenantId)->whereKey($grant->booking_id)->whereNull('idpayment')->get();
            $version = (int) $bookings->first()?->customer_access_version;
        }

        $ids = $bookings->pluck('id')->map(fn ($id) => (int) $id)->all();
        $expected = array_map('intval', $grant->booking_ids ?? []);
        sort($ids);
        sort($expected);
        abort_if($ids === [] || $ids !== $expected || $version !== (int) $grant->family_version
            || $bookings->contains(fn (Booking $booking) => (int) $booking->idtenant !== $tenantId), 404);

        return new AccessContext($grant, $payment, $bookings);
    }
}
