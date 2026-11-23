<?php

namespace App\Actions\Booking;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Booking\BookingState;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\TenantMember;
use Illuminate\Support\Facades\DB;
use stdClass;

final class DecideBookingCancellation
{
    public function __construct(private CancelBooking $cancel) {}

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
                $result = $this->cancel->execute($booking, 'owner', (string) $request->reason, teamDecision: true);
                abort_unless($result['success'], 409, $result['error'] ?? 'Pembatalan tidak dapat disetujui.');
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
