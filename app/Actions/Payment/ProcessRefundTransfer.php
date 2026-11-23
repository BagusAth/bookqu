<?php

namespace App\Actions\Payment;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Integrations\RefundIntegrationEvent;
use App\Jobs\SendRefundCustomerUpdate;
use App\Models\Payment;
use App\Models\Refund;
use App\Support\TenantContext;
use App\Support\TenantDiscovery;
use Illuminate\Support\Facades\DB;

final class ProcessRefundTransfer
{
    public function __construct(private NotifyRefundTeam $notify) {}

    public function execute(Refund $refund, string $result, ?string $reference, ?string $reason): void
    {
        $tenantId = app(TenantDiscovery::class)->payment((int) $refund->payment_id) ?? abort(404);
        app(TenantContext::class)->run($tenantId, fn () => DB::transaction(function () use ($refund, $tenantId, $result, $reference, $reason): void {
            ClaimSlots::lockTenant($tenantId);
            $payment = Payment::findOrFail($refund->payment_id);
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
            if ($result === 'processed') {
                DB::afterCommit(fn () => event(new RefundIntegrationEvent('refund.processed', $tenantId, (int) $current->id)));
            }
            if ($result !== 'pending') {
                DB::afterCommit(fn () => $this->notify->execute($tenantId, $current->id, 'refund_'.$result));
                if ($current->booking_id !== null) {
                    SendRefundCustomerUpdate::dispatch($tenantId, (int) $current->booking_id, $result)->afterCommit();
                }
            }
        }));
    }
}
