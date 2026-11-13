<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Traits\ClearsBookingCache;
use Illuminate\Support\Facades\DB;

/**
 * Marks a paid booking whose session has ended as `no_show` (FR-BOOKING-020, ADR-0018).
 * The whole payment group changes together; payment, balance and refunds stay untouched.
 */
class MarkBookingNoShow
{
    use ClearsBookingCache;

    /** @return array{success: bool, error?: string, booking?: Booking} */
    public function execute(Booking $booking): array
    {
        return DB::transaction(function () use ($booking): array {
            ClaimSlots::lockTenant((int) $booking->idtenant);
            $booking->refresh();

            $group = $this->group($booking);
            foreach ($group as $member) {
                if (! BookingState::canTransition($member->status, BookingState::STATUS_NO_SHOW)) {
                    return ['success' => false, 'error' => 'Hanya booking terkonfirmasi yang bisa ditandai tidak datang.'];
                }
            }
            if (! BookingRules::sessionHasEnded($group)) {
                return ['success' => false, 'error' => 'Booking baru bisa ditandai tidak datang setelah sesinya selesai.'];
            }

            foreach ($group as $member) {
                if ($member->status !== BookingState::STATUS_NO_SHOW) {
                    $member->update(['status' => BookingState::STATUS_NO_SHOW]);
                    BookingLog::record($member->id, BookingState::STATUS_NO_SHOW, 'Pelanggan tidak datang.');
                }
            }

            return ['success' => true, 'booking' => $booking->refresh()];
        });
    }

    /** @return list<Booking> the booking and every booking that shares its payment */
    private function group(Booking $booking): array
    {
        if (empty($booking->idpayment)) {
            return [$booking];
        }

        // scope-bypass: members of this booking's own payment group, filtered by its tenant
        return Booking::withoutGlobalScopes()
            ->where('idtenant', $booking->idtenant)
            ->where('idpayment', $booking->idpayment)
            ->with('schedule')
            ->orderBy('id')
            ->get()
            ->all();
    }
}
