<?php

namespace App\Actions\Payment;

use App\Actions\Schedule\ClaimSlots;
use App\Jobs\SendRefundCustomerUpdate;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

final class ProcessRefundTransfer
{
    public function __construct(private NotifyRefundTeam $notify) {}

    public function execute(Refund $refund, string $result, ?string $reference, ?string $reason): void
    {
        // scope-bypass: platform admin processes the refund's stored payment; recheck under the tenant lock.
        $payment = Payment::withoutGlobalScopes()->findOrFail($refund->payment_id);
        DB::transaction(function () use ($refund, $payment, $result, $reference, $reason): void {
            $tenantId = (int) $payment->idtenant;
            ClaimSlots::lockTenant($tenantId);
            $current = Refund::where('payment_id', $payment->id)->lockForUpdate()->findOrFail($refund->id);
            if ($current->status === $result) {
                return;
            }

            abort_unless(($current->status === 'pending' && in_array($result, ['processed', 'failed'], true))
                || ($current->status === 'failed' && $result === 'pending'), 409);
            $current->update([
                'status' => $result,
                'refund_reference' => $result === 'processed' ? $reference : null,
                'catatan' => $result === 'failed' ? $reason : $current->catatan,
            ]);
            if ($result !== 'pending') {
                DB::afterCommit(fn () => $this->notify->execute($tenantId, $current->id, 'refund_'.$result));
                if ($current->booking_id !== null) {
                    SendRefundCustomerUpdate::dispatch($tenantId, (int) $current->booking_id, $result)->afterCommit();
                }
            }
        });
    }
}
