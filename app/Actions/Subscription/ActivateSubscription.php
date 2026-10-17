<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Domain\Subscription\SubscriptionState;
use App\Models\Payment;
use App\Models\Subscription;

class ActivateSubscription
{
    /**
     * Idempotently activate or renew subscription for a successful payment.
     */
    public function execute(Payment $payment): Subscription
    {
        $existing = Subscription::withoutGlobalScopes()
            ->where('idtenant', $payment->idtenant)
            ->where('idplan', $payment->idplan)
            ->where('status', SubscriptionState::STATUS_ACTIVE)
            ->where('created_at', '>=', $payment->created_at)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Expire older active/trial subscriptions
        Subscription::withoutGlobalScopes()
            ->where('idtenant', $payment->idtenant)
            ->whereIn('status', [SubscriptionState::STATUS_TRIAL, SubscriptionState::STATUS_ACTIVE])
            ->update(['status' => SubscriptionState::STATUS_EXPIRED]);

        return Subscription::create([
            'idtenant'           => $payment->idtenant,
            'idplan'             => $payment->idplan,
            'status'             => SubscriptionState::STATUS_ACTIVE,
            'langganan_mulai'    => now(),
            'langganan_berakhir' => now()->addMonth(),
        ]);
    }
}
