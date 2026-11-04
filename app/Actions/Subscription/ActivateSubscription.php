<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class ActivateSubscription
{
    public function execute(Payment $payment): Subscription
    {
        return DB::transaction(function () use ($payment): Subscription {
            ClaimSlots::lockTenant((int) $payment->idtenant);
            // scope-bypass: payment tenant is stored trusted provider evidence; keep the same tenant under lock.
            $payment = Payment::withoutGlobalScopes()->where('idtenant', $payment->idtenant)->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'sukses' || $payment->tipe !== 'subscription' || $payment->idplan === null) {
                throw new \DomainException('Subscription activation requires a settled subscription payment.');
            }
            // scope-bypass: activation identity belongs only to this verified payment and stored tenant.
            $existing = Subscription::withoutGlobalScopes()->where('idtenant', $payment->idtenant)->where('source_payment_id', $payment->id)->first();
            if ($existing) {
                return $existing;
            }
            // scope-bypass: renewal reads only the verified payment tenant under its tenant-first lock.
            $current = Subscription::withoutGlobalScopes()->where('idtenant', $payment->idtenant)->latest('id')->lockForUpdate()->first();
            $start = now();
            if ($current && $current->status === SubscriptionState::STATUS_ACTIVE && (int) $current->idplan === (int) $payment->idplan
                && $current->langganan_berakhir !== null && $current->langganan_berakhir->isFuture()) {
                $start = $current->langganan_berakhir->copy();
            }
            // scope-bypass: only older periods of this verified payment tenant are superseded.
            Subscription::withoutGlobalScopes()->where('idtenant', $payment->idtenant)
                ->whereIn('status', [SubscriptionState::STATUS_TRIAL, SubscriptionState::STATUS_ACTIVE])->update(['status' => SubscriptionState::STATUS_EXPIRED]);

            return Subscription::create(['idtenant' => $payment->idtenant, 'idplan' => $payment->idplan, 'source_payment_id' => $payment->id,
                'status' => SubscriptionState::STATUS_ACTIVE, 'langganan_mulai' => now(), 'langganan_berakhir' => $start->addMonthNoOverflow()]);
        });
    }
}
