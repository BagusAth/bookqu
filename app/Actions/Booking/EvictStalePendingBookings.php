<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Payment\ExpirePayment;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Models\Booking;
use App\Models\Payment;

class EvictStalePendingBookings
{
    public function __construct(
        protected ExpirePayment $expirePayment
    ) {}

    /**
     * Evict (expire/cancel) stale pending bookings that have exceeded the grace period.
     * Must be called within a database transaction where target schedules are locked.
     *
     * @param array<int>|int $scheduleIds
     * @return int Number of stale bookings evicted
     */
    public function execute(array|int $scheduleIds): int
    {
        $ids = is_array($scheduleIds) ? $scheduleIds : [$scheduleIds];
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return 0;
        }

        $cutoff = now()->subMinutes(BookingRules::PENDING_GRACE_MINUTES);

        // Lock and retrieve stale pending bookings on these schedules
        $staleBookings = Booking::withoutGlobalScopes()
            ->whereIn('idschedule', $ids)
            ->where('status', BookingState::STATUS_PENDING)
            ->where('created_at', '<', $cutoff)
            ->lockForUpdate()
            ->get();

        $evictedCount = 0;
        $handledPaymentIds = [];

        foreach ($staleBookings as $booking) {
            if ($booking->idpayment && !in_array($booking->idpayment, $handledPaymentIds, true)) {
                $payment = Payment::withoutGlobalScopes()->lockForUpdate()->find($booking->idpayment);
                if ($payment) {
                    $this->expirePayment->execute($payment);
                    $handledPaymentIds[] = $booking->idpayment;
                }
            }

            // Ensure booking is cancelled if not handled by expirePayment
            $booking->refresh();
            if ($booking->status === BookingState::STATUS_PENDING) {
                $booking->update(['status' => BookingState::STATUS_CANCELLED]);
            }

            $evictedCount++;
        }

        return $evictedCount;
    }
}
