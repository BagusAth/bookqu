<?php

namespace App\Actions\Booking;

use App\Domain\Payment\PaymentState;
use App\Models\Payment;
use App\Models\Tenant;

final class GetAddOnSales
{
    /** @return array<int, int> */
    public function execute(Tenant $tenant): array
    {
        $sales = [];
        $payments = Payment::query()->where('idtenant', $tenant->id)->where('tipe', 'booking')
            ->where('status', PaymentState::STATUS_SUKSES)->whereNotNull('price_breakdown')
            ->select(['id', 'price_breakdown'])->lazyById();
        foreach ($payments as $payment) {
            foreach ($payment->price_breakdown['add_ons'] ?? [] as $line) {
                $id = (int) $line['id'];
                $sales[$id] = ($sales[$id] ?? 0) + (int) $line['quantity'];
            }
        }

        return $sales;
    }
}
