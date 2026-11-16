<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingPricing;
use App\Models\Payment;

final class GetInvoiceAmounts
{
    /** @return array{invoiceAmount: string, invoiceSlotAmount: string, invoiceAmountNote: string, invoiceSlotAmounts: array<int, string>} */
    public function execute(Payment $payment): array
    {
        $total = 'Rp '.number_format((float) $payment->jumlah, 0, ',', '.');
        $bookings = $payment->bookings()->where('bookings.idtenant', $payment->idtenant)->get(['id', 'idschedule']);
        $group = $bookings->count() > 1;
        $slotAmounts = [];
        foreach ($bookings as $booking) {
            $amount = BookingPricing::frozenSlotAmount($payment, (int) $booking->idschedule);
            if ($amount !== null) {
                $slotAmounts[$booking->id] = 'Rp '.number_format($amount, 0, ',', '.');
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
