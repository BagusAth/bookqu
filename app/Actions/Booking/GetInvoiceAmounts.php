<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingPricing;
use App\Models\Payment;
use App\Support\Rupiah;

final class GetInvoiceAmounts
{
    /** @return array{invoiceAmount: string, invoiceSlotAmount: string, invoiceAmountNote: string, invoiceSlotAmounts: array<int, string>} */
    public function execute(Payment $payment): array
    {
        $total = Rupiah::format($payment->jumlah);
        $bookings = $payment->bookings()->where('bookings.idtenant', $payment->idtenant)->get(['id', 'idschedule']);
        $group = $bookings->count() > 1;
        $lines = $payment->price_breakdown['lines'] ?? [];
        $slotAmounts = [];
        foreach ($bookings as $booking) {
            $slotId = ! $group && is_array($lines) && count($lines) === 1 && isset($lines[0]['schedule_id'])
                ? (int) $lines[0]['schedule_id']
                : (int) $booking->idschedule;
            $amount = BookingPricing::frozenSlotAmount($payment, $slotId);
            if ($amount !== null) {
                $slotAmounts[$booking->id] = Rupiah::format($amount);
            }
        }

        return [
            'invoiceAmount' => $total,
            'invoiceSlotAmount' => $group ? 'Termasuk dalam total transaksi' : $total,
            'invoiceSlotAmounts' => $slotAmounts,
            'invoiceAmountNote' => $group && count($slotAmounts) < $bookings->count() ? 'Total berlaku untuk seluruh transaksi. Rincian harga historis per sesi tidak tersedia.' : '',
        ];
    }
}
