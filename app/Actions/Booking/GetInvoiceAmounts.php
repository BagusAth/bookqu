<?php

namespace App\Actions\Booking;

use App\Models\Payment;

final class GetInvoiceAmounts
{
    /** @return array{invoiceAmount: string, invoiceSlotAmount: string, invoiceAmountNote: string} */
    public function execute(Payment $payment): array
    {
        $total = 'Rp '.number_format((float) $payment->jumlah, 0, ',', '.');
        $group = $payment->bookings()->count() > 1;

        return [
            'invoiceAmount' => $total,
            'invoiceSlotAmount' => $group ? 'Termasuk dalam total transaksi' : $total,
            'invoiceAmountNote' => $group ? 'Total berlaku untuk seluruh transaksi. Rincian harga historis per sesi tidak tersedia.' : '',
        ];
    }
}
