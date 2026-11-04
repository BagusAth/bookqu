<?php

namespace App\Actions\Payment;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Payment\RefundRequestState;
use App\Models\RefundRequest;
use Illuminate\Support\Facades\DB;

final class RemindOpenRefundRequests
{
    public function __construct(private NotifyRefundTeam $notify) {}

    public function execute(): void
    {
        $date = now('Asia/Jakarta')->toDateString();
        // scope-bypass: scheduler enumerates only identifiers; each request is rechecked under its stored tenant lock.
        RefundRequest::withoutGlobalScopes()->where('status', RefundRequestState::Open->value)
            ->where(fn ($query) => $query->whereNull('last_reminded_on')->orWhere('last_reminded_on', '<', $date))
            ->select(['id', 'idtenant'])->eachById(function (RefundRequest $candidate) use ($date): void {
                DB::transaction(function () use ($candidate, $date): void {
                    $tenantId = (int) $candidate->idtenant;
                    ClaimSlots::lockTenant($tenantId);
                    // scope-bypass: scheduler rechecks the same request inside its tenant lock.
                    $request = RefundRequest::withoutGlobalScopes()->where('idtenant', $tenantId)->lockForUpdate()->find($candidate->id);
                    if ($request?->status !== RefundRequestState::Open || $request->last_reminded_on === $date) {
                        return;
                    }
                    $request->update(['last_reminded_on' => $date]);
                    DB::afterCommit(fn () => $this->notify->execute($tenantId, $request->id, 'refund_reminder'));
                });
            });
    }
}
