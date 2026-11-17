<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Models\Booking;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Completes several ended sessions at once: bulk completion by a team member (FR-BOOKING-025)
 * and automatic completion by the scheduler (FR-BOOKING-024). Every booking goes through
 * `UpdateBookingStatus`, so the state machine and payment rules stay in one place; this
 * Action only decides which bookings are eligible and reports what it skipped.
 */
final class CompleteBookings
{
    /** Hours after the session ends before the system completes a booking (D-026). */
    public const AUTO_COMPLETE_AFTER_HOURS = 6;

    public function __construct(private UpdateBookingStatus $updateBookingStatus) {}

    /**
     * @param  list<int>  $bookingIds  ids from the request; anything outside the tenant is skipped
     * @return array{completed: int, skipped: int}
     */
    public function execute(Tenant $tenant, array $bookingIds, string $actor, ?Carbon $endedBefore = null): array
    {
        $endedBefore ??= Carbon::now('Asia/Jakarta');
        $ids = array_values(array_unique(array_map('intval', $bookingIds)));
        $completed = 0;

        // scope-bypass: the scheduler has no tenant context; every row is filtered to this tenant.
        $bookings = Booking::withoutGlobalScopes()->where('idtenant', $tenant->id)->whereIn('id', $ids)->get();

        foreach ($bookings as $booking) {
            $done = DB::transaction(function () use ($tenant, $booking, $actor, $endedBefore): bool {
                ClaimSlots::lockTenant((int) $tenant->id);
                $booking->refresh();
                $booking->load('schedule');

                if (! $this->isEligible($booking, $endedBefore)) {
                    return false;
                }

                return $this->updateBookingStatus
                    ->execute($booking, BookingState::STATUS_COMPLETED, $actor, notify: false)['success'];
            });

            $completed += $done ? 1 : 0;
        }

        return ['completed' => $completed, 'skipped' => count($ids) - $completed];
    }

    /**
     * Paid bookings of the tenant whose session ended before the cutoff, oldest first.
     *
     * @return list<int>
     */
    public function endedIds(Tenant $tenant, Carbon $endedBefore): array
    {
        // scope-bypass: used by the scheduler without tenant context; filtered to this tenant.
        return Booking::withoutGlobalScopes()
            ->where('idtenant', $tenant->id)
            ->where('status', BookingState::STATUS_PAID)
            ->whereDate('tanggalbooking', '<=', $endedBefore->toDateString())
            ->with('schedule')
            ->orderBy('tanggalbooking')
            ->get()
            ->filter(fn (Booking $booking): bool => BookingRules::sessionHasEnded([$booking], $endedBefore))
            ->map(fn (Booking $booking): int => (int) $booking->id)
            ->values()
            ->all();
    }

    private function isEligible(Booking $booking, Carbon $endedBefore): bool
    {
        if ($booking->status !== BookingState::STATUS_PAID || ! BookingRules::sessionHasEnded([$booking], $endedBefore)) {
            return false;
        }

        $openRefund = $booking->idpayment !== null && DB::table('refund_requests')
            ->where('idtenant', $booking->idtenant)->where('idpayment', $booking->idpayment)->where('status', 'open')->exists();
        $pendingCancellation = DB::table('booking_cancellation_requests')
            ->where('idtenant', $booking->idtenant)->where('booking_id', $booking->id)->where('status', 'pending')->exists();

        return ! $openRefund && ! $pendingCancellation;
    }
}
