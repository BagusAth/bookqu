<?php

namespace App\Actions\Booking;

use App\Models\AdditionalItem;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

final class BookingPricingReservations
{
    /** Called inside the placement transaction after locking the tenant and storing the quote. */
    public function reserve(Payment $payment): void
    {
        $quote = $payment->price_breakdown;
        $rows = [];
        if (isset($quote['promo'])) {
            $rows[] = ['payment_id' => $payment->id, 'voucher_id' => $quote['promo']['id'], 'additional_item_id' => null, 'quantity' => 1];
        }
        foreach ($quote['add_ons'] ?? [] as $item) {
            if (! $item['unlimited']) {
                $rows[] = ['payment_id' => $payment->id, 'voucher_id' => null, 'additional_item_id' => $item['id'], 'quantity' => $item['quantity']];
            }
        }
        if ($rows !== []) {
            DB::table('booking_pricing_reservations')->insert($rows);
        }
    }

    public function held(string $column, int $id): int
    {
        return (int) DB::table('booking_pricing_reservations')->where($column, $id)->sum('quantity');
    }

    /** Called under the tenant lock, once on the payment's terminal transition. */
    public function finish(Payment $payment, bool $paid): void
    {
        $reservations = DB::table('booking_pricing_reservations')->where('payment_id', $payment->id);
        if ($paid) {
            foreach ($reservations->get() as $reservation) {
                if ($reservation->voucher_id !== null) {
                    // scope-bypass: settlement uses the stored payment tenant, including synchronous placement.
                    Voucher::withoutGlobalScopes()->where('idtenant', $payment->idtenant)
                        ->whereKey($reservation->voucher_id)->increment('used_count', $reservation->quantity);
                } else {
                    // scope-bypass: settlement uses the stored payment tenant, including synchronous placement.
                    AdditionalItem::withoutGlobalScopes()->where('idtenant', $payment->idtenant)
                        ->whereKey($reservation->additional_item_id)->decrement('stock', $reservation->quantity);
                }
            }
        }
        $reservations->delete();
    }
}
