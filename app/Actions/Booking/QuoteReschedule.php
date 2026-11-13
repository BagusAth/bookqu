<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingPricing;
use App\Domain\Schedule\PriceRules;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;

final class QuoteReschedule
{
    /** @param iterable<Schedule> $slots
     * @return array<int, array{price: int, price_difference: ?int}>
     */
    public function execute(Booking $booking, Tenant $tenant, Service $service, string $date, iterable $slots): array
    {
        $payment = $booking->payment;
        $previous = null;
        if ($payment instanceof Payment && (int) $payment->idtenant === (int) $tenant->id) {
            $originalSlot = (int) (count($payment->price_breakdown['lines'] ?? []) === 1
                ? $payment->price_breakdown['lines'][0]['schedule_id'] : $booking->idschedule);
            $previous = BookingPricing::frozenSlotAmount($payment, $originalSlot) ?? (int) $payment->jumlah;
        }

        $quote = BookingPricing::quote($service, $slots, PriceRules::forTenant($tenant, $date, $date), max(1, (int) $booking->peserta));
        $result = [];
        foreach ($quote['lines'] as $line) {
            $result[$line['schedule_id']] = [
                'price' => $line['amount'],
                'price_difference' => $previous === null ? null : $line['amount'] - $previous,
            ];
        }

        return $result;
    }
}
