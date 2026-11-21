<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OwnerPayout;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Scopes\TenantScope;

final class TenantDiscovery
{
    public function payment(int $paymentId): ?int
    {
        // scope-bypass: identify the owning tenant of a stored payment before entering the runner.
        $tenantId = Payment::withoutGlobalScope(TenantScope::class)->whereKey($paymentId)->value('idtenant');

        return $tenantId === null ? null : (int) $tenantId;
    }

    public function payout(int $payoutId): ?int
    {
        // scope-bypass: identify the owning tenant before a platform admin processes a payout.
        $tenantId = OwnerPayout::withoutGlobalScope(TenantScope::class)->whereKey($payoutId)->value('idtenant');

        return $tenantId === null ? null : (int) $tenantId;
    }

    /** @param list<int> $ids */
    public function hasForeignSchedule(array $ids, int $tenantId): bool
    {
        // scope-bypass: classify a refused slot claim without reading or locking the foreign schedule.
        return Schedule::withoutGlobalScope(TenantScope::class)->whereKey($ids)->where('idtenant', '!=', $tenantId)->exists();
    }

    public function diagnosticPayment(string $orderId): ?Payment
    {
        // scope-bypass: the non-production webhook simulator resolves the payment by its public order reference.
        return Payment::withoutGlobalScope(TenantScope::class)->where('order_id', $orderId)->first();
    }
}
