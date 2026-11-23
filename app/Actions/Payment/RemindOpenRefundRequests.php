<?php

namespace App\Actions\Payment;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Payment\RefundRequestState;
use App\Models\RefundRequest;
use App\Support\TenantContext;
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
                $tenantId = (int) $candidate->idtenant;
                app(TenantContext::class)->run($tenantId, fn () => DB::transaction(function () use ($candidate, $date, $tenantId): void {
                    ClaimSlots::lockTenant($tenantId);
                    $request = RefundRequest::lockForUpdate()->find($candidate->id);
                    if ($request?->status !== RefundRequestState::Open || $request->last_reminded_on === $date) {
                        return;
                    }
                    $request->update(['last_reminded_on' => $date]);
                    DB::afterCommit(fn () => $this->notify->execute($tenantId, $request->id, 'refund_reminder'));
                }));
            });
    }
}
