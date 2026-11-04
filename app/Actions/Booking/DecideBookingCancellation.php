<?php

namespace App\Actions\Booking;

use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Schedule\ClaimSlots;
use App\Domain\Booking\BookingState;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\TenantMember;
use Illuminate\Support\Facades\DB;
use stdClass;

final class DecideBookingCancellation
{
    public function __construct(private CancelBooking $cancel, private PaymentLifecycle $payments) {}

    public function request(Booking $booking, TenantMember $actor, string $reason): void
    {
        DB::transaction(function () use ($booking, $actor, $reason): void {
            ClaimSlots::lockTenant((int) $booking->idtenant);
            $booking->refresh();
            $this->assertCancellable($booking);
            $group = Booking::where('idtenant', $booking->idtenant)->when(
                $booking->idpayment,
                fn ($query) => $query->where('idpayment', $booking->idpayment),
                fn ($query) => $query->whereKey($booking->id),
            )->select('id');
            abort_if(DB::table('booking_cancellation_requests')->where('idtenant', $booking->idtenant)
                ->whereIn('booking_id', $group)->where('status', 'pending')->exists(), 409);
            DB::table('booking_cancellation_requests')->insert([
                'idtenant' => $booking->idtenant, 'booking_id' => $booking->id, 'requested_by' => $actor->iduser,
                'reason' => $reason, 'created_at' => now(),
            ]);
            BookingLog::record($booking->id, 'cancellation_requested', 'Permintaan pembatalan diajukan.');
        });
    }

    public function decide(int $id, TenantMember $actor, bool $approve): void
    {
        $tenantId = (int) $actor->idtenant;
        DB::transaction(function () use ($id, $actor, $tenantId, $approve): void {
            ClaimSlots::lockTenant($tenantId);
            $request = DB::table('booking_cancellation_requests')->where('idtenant', $tenantId)->lockForUpdate()->find($id);
            abort_unless($request instanceof stdClass, 404);
            abort_unless($request->status === 'pending', 409);
            $booking = Booking::where('idtenant', $tenantId)->findOrFail($request->booking_id);

            if ($approve) {
                $this->assertCancellable($booking);
                $payment = $booking->payment;
                abort_if($booking->idpayment && (! $payment instanceof Payment || (int) $payment->idtenant !== $tenantId), 409);
                if ($payment instanceof Payment && $payment->status === PaymentState::STATUS_PENDING) {
                    abort_unless($this->payments->transition($payment, PaymentTransition::CancelledByBusiness)->status() === PaymentState::STATUS_GAGAL, 409);
                    $booking->refresh();
                }
                $result = $this->cancel->execute($booking, 'owner', (string) $request->reason, teamDecision: true);
                abort_unless($result['success'], 409);
            }

            DB::table('booking_cancellation_requests')->where('id', $id)->update([
                'status' => $approve ? 'approved' : 'rejected', 'decided_by' => $actor->iduser, 'decided_at' => now(),
            ]);
            BookingLog::record($booking->id, $approve ? 'cancellation_approved' : 'cancellation_rejected',
                $approve ? 'Permintaan pembatalan disetujui.' : 'Permintaan pembatalan ditolak.');
        });
    }

    private function assertCancellable(Booking $booking): void
    {
        abort_unless(in_array($booking->status, [BookingState::STATUS_PAID, BookingState::STATUS_PENDING], true), 409);
        if ($booking->idpayment) {
            abort_if(Booking::where('idtenant', $booking->idtenant)->where('idpayment', $booking->idpayment)
                ->whereNotIn('status', [BookingState::STATUS_PAID, BookingState::STATUS_PENDING, BookingState::STATUS_CANCELLED])->exists(), 409);
        }
    }
}
