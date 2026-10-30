<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Schedule\SlotClaimRefused;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Domain\Schedule\SlotRefusal;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class EvictStalePendingBookings
{
    public function __construct(
        protected PaymentLifecycle $paymentLifecycle
    ) {}

    /**
     * Evict (expire/cancel) stale pending bookings that have exceeded the grace period.
     * Must be called within a database transaction where target schedules are locked.
     *
     * @param  array<int>|int  $scheduleIds
     * @return int Number of stale bookings evicted
     */
    public function execute(array|int $scheduleIds): int
    {
        $ids = is_array($scheduleIds) ? $scheduleIds : [$scheduleIds];
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return 0;
        }

        // Lock and retrieve pending bookings on these schedules that no longer hold their
        // slot: the single occupancy rule decides (payment deadline + margin, ADR-0009).
        $staleBookings = Booking::withoutGlobalScopes()
            ->whereIn('idschedule', $ids)
            ->where('status', BookingState::STATUS_PENDING)
            ->whereNot(fn ($query) => BookingRules::applyOccupiesSlotCondition($query, 'bookings.'))
            ->lockForUpdate()
            ->get();

        $evictedCount = 0;
        $handledPaymentIds = [];

        foreach ($staleBookings as $booking) {
            if ($booking->idpayment && ! in_array($booking->idpayment, $handledPaymentIds, true)) {
                // scope-bypass: the payment of a booking already verified to belong to the claimed schedules.
                $payment = Payment::withoutGlobalScopes()->find($booking->idpayment);

                // A pending booking on a successful payment is an anomaly: refuse the claim
                // instead of cancelling a paid reservation (ADR-0005 decision 6).
                if ($payment && $payment->status === PaymentState::STATUS_SUKSES) {
                    Log::warning('EvictStalePendingBookings: pending booking attached to a successful payment', [
                        'booking_id' => $booking->id,
                        'payment_id' => $payment->id,
                        'schedule_id' => $booking->idschedule,
                    ]);

                    throw new SlotClaimRefused(SlotRefusal::InconsistentGroup, (int) $booking->idschedule, substr((string) $booking->jam, 0, 5));
                }

                // The whole payment group expires through the Payment lifecycle.
                if ($payment) {
                    $this->paymentLifecycle->transition($payment, PaymentTransition::Expired);
                    $handledPaymentIds[] = $booking->idpayment;
                }
            }

            // Ensure booking is cancelled if the payment expiry did not cancel it
            $booking->refresh();
            if ($booking->status === BookingState::STATUS_PENDING) {
                $booking->update(['status' => BookingState::STATUS_CANCELLED]);
            }

            $evictedCount++;
        }

        return $evictedCount;
    }
}
