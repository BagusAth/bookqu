<?php

namespace App\Actions\Booking;

use App\Domain\Payment\PaymentState;
use App\Models\Payment;
use App\Models\Tenant;

final class GetPromoFigures
{
    /** @return array{uses: int, discount: int, revenue: int} */
    public function execute(Tenant $tenant): array
    {
        $figures = ['uses' => 0, 'discount' => 0, 'revenue' => 0];
        $payments = Payment::where('idtenant', $tenant->id)->where('tipe', 'booking')
            ->where('status', PaymentState::STATUS_SUKSES)->whereNotNull('price_breakdown')
            ->lazyById(500, 'id');
        foreach ($payments as $payment) {
            $promo = $payment->price_breakdown['promo'] ?? null;
            if ($promo !== null) {
                $figures['uses']++;
                $figures['discount'] += (int) $promo['discount'];
                $figures['revenue'] += (int) $payment->jumlah;
            }
        }

        return $figures;
    }
}
