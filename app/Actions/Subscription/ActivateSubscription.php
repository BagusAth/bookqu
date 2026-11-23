<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Payment;
use App\Models\Subscription;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class ActivateSubscription
{
    public function execute(Payment $payment): Subscription
    {
        return app(TenantContext::class)->run((int) $payment->idtenant, fn (): Subscription => DB::transaction(function () use ($payment): Subscription {
            ClaimSlots::lockTenant((int) $payment->idtenant);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'sukses' || $payment->tipe !== 'subscription' || $payment->idplan === null) {
                throw new \DomainException('Subscription activation requires a settled subscription payment.');
            }
            $existing = Subscription::where('source_payment_id', $payment->id)->first();
            if ($existing) {
                return $existing;
            }
            $current = Subscription::latest('id')->lockForUpdate()->first();
            $end = SubscriptionState::paidPeriodEnd($current, (int) $payment->idplan, now());
            Subscription::whereIn('status', [SubscriptionState::STATUS_TRIAL, SubscriptionState::STATUS_ACTIVE])->update(['status' => SubscriptionState::STATUS_EXPIRED]);

            return Subscription::create(['idtenant' => $payment->idtenant, 'idplan' => $payment->idplan, 'source_payment_id' => $payment->id,
                'status' => SubscriptionState::STATUS_ACTIVE, 'langganan_mulai' => now(), 'langganan_berakhir' => $end]);
        }));
    }
}
