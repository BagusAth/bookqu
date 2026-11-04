<?php

namespace App\Actions\Payment;

use App\Actions\Booking\CancelBooking;
use App\Actions\Schedule\ClaimSlots;
use App\Domain\Payment\RefundRequestState;
use App\Jobs\SendRefundCustomerUpdate;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DecideRefundRequest
{
    public function __construct(private CancelBooking $cancel) {}

    public function execute(int $tenantId, int $id, User $actor, RefundRequestState $decision, ?string $reason): void
    {
        DB::transaction(function () use ($tenantId, $id, $actor, $decision, $reason): void {
            ClaimSlots::lockTenant($tenantId);
            $request = RefundRequest::where('idtenant', $tenantId)->lockForUpdate()->findOrFail($id);
            if ($request->status === $decision) {
                return;
            }
            abort_unless($request->status === RefundRequestState::Open && $decision !== RefundRequestState::Open, 409);
            if ($decision === RefundRequestState::Rejected && blank($reason)) {
                throw ValidationException::withMessages(['reason' => 'Alasan penolakan wajib diisi.']);
            }
            if ($decision !== RefundRequestState::Rejected) {
                $booking = $request->booking;
                abort_if($booking === null || (int) $booking->idpayment !== (int) $request->idpayment, 409);
                $result = $this->cancel->execute($booking, 'owner', 'Pembatalan setelah persetujuan refund.', refundRequest: $request, refundDecision: $decision);
                abort_unless($result['success'], 409, $result['error'] ?? 'Pembatalan gagal.');
            }

            $request->update(['status' => $decision, 'decided_by' => $actor->id, 'decision_reason' => $reason, 'decided_at' => now()]);
            SendRefundCustomerUpdate::dispatch($tenantId, (int) $request->booking_id, $decision->value)->afterCommit();
        });
    }
}
