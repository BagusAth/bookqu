<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Domain\Payment\PaymentState;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Tenant;

/**
 * Platform balance movements caused by payments (BQ-015, fee 0%). Called by the Payment
 * lifecycle inside its tenant-locked transaction; `balance_credited_at` makes crediting idempotent.
 */
class TenantBalanceLedger
{
    public function creditPaidBooking(Payment $payment): void
    {
        if ($payment->tipe !== PaymentState::TIPE_BOOKING || (float) $payment->jumlah <= 0) {
            return;
        }

        // scope-bypass: the payment is already resolved and locked by the lifecycle
        $marked = Payment::withoutGlobalScopes()
            ->whereKey($payment->id)
            ->whereNull('balance_credited_at')
            ->update(['balance_credited_at' => now()]);

        if ($marked === 1) {
            Tenant::query()->whereKey($payment->idtenant)->increment('saldo_platform', (float) $payment->jumlah);
        }
    }

    public function debitRefund(Payment $payment, Refund $refund): void
    {
        if ($payment->balance_credited_at === null) {
            return;
        }

        Tenant::query()->whereKey($payment->idtenant)->decrement('saldo_platform', (float) $refund->jumlah);
    }
}
