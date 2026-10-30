<?php

namespace App\Domain\Booking;

use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingRules
{
    /**
     * Validate that multi-slot schedules are contiguous without any gaps.
     * Assumes schedules are already sorted by jam_mulai ascending.
     */
    public static function validateContiguousSlots(array $orderedSchedules, int $durationMinutes, string $date, string $timezone = 'Asia/Jakarta'): bool
    {
        if (count($orderedSchedules) <= 1) {
            return true;
        }

        for ($i = 1; $i < count($orderedSchedules); $i++) {
            $prev = is_array($orderedSchedules[$i - 1]) ? (object) $orderedSchedules[$i - 1] : $orderedSchedules[$i - 1];
            $curr = is_array($orderedSchedules[$i]) ? (object) $orderedSchedules[$i] : $orderedSchedules[$i];

            $prevStart = Carbon::parse($date.' '.$prev->jam_mulai, $timezone);
            $currStart = Carbon::parse($date.' '.$curr->jam_mulai, $timezone);

            if ((int) abs($currStart->diffInMinutes($prevStart)) !== $durationMinutes) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate prices for a collection of schedules based on schedule override or service base price.
     *
     * @return array{slot_prices: float[], total: float}
     */
    public static function calculateSlotPrices(iterable $schedules, Service $service): array
    {
        $slotPrices = [];
        foreach ($schedules as $schedule) {
            $schedObj = is_array($schedule) ? (object) $schedule : $schedule;
            $price = ($schedObj->harga_override !== null && $schedObj->harga_override !== '')
                ? (float) $schedObj->harga_override
                : (float) $service->harga;
            $slotPrices[] = (float) max(0, $price);
        }

        $total = max(0, array_sum($slotPrices));

        return [
            'slot_prices' => $slotPrices,
            'total' => $total,
        ];
    }

    /**
     * Apply the slot-occupying conditions to a query builder, join clause, or eloquent builder.
     * This is the single home of the occupancy rule (INV-BOOK-03).
     *
     * Occupied: `paid`, `completed`, and `pending` while its payment is `pending` and
     * `now < payments.expired_at + settlement margin` (ADR-0009 decision 2). Legacy pending
     * bookings whose payment has no deadline hold the slot for the booking payment window
     * plus margin from their creation.
     *
     * Not occupied: `cancelled`, and `pending` once its payment failed, succeeded without
     * marking it paid (anomaly), or passed its deadline plus margin.
     */
    public static function applyOccupiesSlotCondition(mixed $query, string $prefix = 'bookings.'): mixed
    {
        $prefix = $prefix !== '' ? rtrim($prefix, '.').'.' : '';
        $holdCutoff = now()->subMinutes(PaymentRules::settlementMarginMinutes());
        $legacyCutoff = now()->subMinutes(PaymentRules::deadlineMinutes(PaymentState::TIPE_BOOKING) + PaymentRules::settlementMarginMinutes());
        $paymentOfBooking = $prefix === '' ? 'bookings.idpayment' : $prefix.'idpayment';

        return $query->where(function ($q) use ($prefix, $holdCutoff, $legacyCutoff, $paymentOfBooking) {
            $q->whereIn($prefix.'status', [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED])
                ->orWhere(function ($pending) use ($prefix, $holdCutoff, $legacyCutoff, $paymentOfBooking) {
                    $pending->where($prefix.'status', BookingState::STATUS_PENDING)
                        ->where(function ($hold) use ($prefix, $holdCutoff, $legacyCutoff, $paymentOfBooking) {
                            $hold->whereExists(function ($payment) use ($holdCutoff, $paymentOfBooking) {
                                $payment->selectRaw('1')
                                    ->from('payments')
                                    ->whereColumn('payments.id', $paymentOfBooking)
                                    ->where('payments.status', PaymentState::STATUS_PENDING)
                                    ->where('payments.expired_at', '>', $holdCutoff);
                            })->orWhere(function ($legacy) use ($prefix, $legacyCutoff, $paymentOfBooking) {
                                $legacy->where($prefix.'created_at', '>=', $legacyCutoff)
                                    ->whereNotExists(function ($payment) use ($paymentOfBooking) {
                                        $payment->selectRaw('1')
                                            ->from('payments')
                                            ->whereColumn('payments.id', $paymentOfBooking)
                                            ->where(function ($decided) {
                                                $decided->whereNotNull('payments.expired_at')
                                                    ->orWhere('payments.status', '!=', PaymentState::STATUS_PENDING);
                                            });
                                    });
                            });
                        });
                });
        });
    }

    /**
     * Check if a slot is already occupied by an active booking.
     */
    public static function isSlotOccupied(int $scheduleId, ?int $excludeBookingId = null): bool
    {
        $query = DB::table('bookings')
            ->where('idschedule', $scheduleId)
            ->when($excludeBookingId !== null, fn ($q) => $q->where('id', '!=', $excludeBookingId));

        return self::applyOccupiesSlotCondition($query, '')->exists();
    }

    /**
     * Verify whether a booking can be cancelled by a customer based on business policy.
     */
    public static function canCancel(Booking $booking): bool
    {
        if ($booking->status !== BookingState::STATUS_PAID) {
            return false;
        }

        if ($booking->isMultiSlot()) {
            return false;
        }

        $tenant = $booking->tenant;
        if (! $tenant) {
            return false;
        }

        $cancelBeforeHours = $tenant->cancel_before_hours ?? 24;
        $bookingDateStr = $booking->tanggalbooking instanceof Carbon
            ? $booking->tanggalbooking->toDateString()
            : Carbon::parse($booking->tanggalbooking)->toDateString();
        $bookingDateTime = Carbon::parse($bookingDateStr.' '.$booking->jam);

        return now()->addHours($cancelBeforeHours)->lessThan($bookingDateTime);
    }

    /**
     * Verify whether a booking can be rescheduled by a customer based on business policy.
     */
    public static function canReschedule(Booking $booking): bool
    {
        if ($booking->status !== BookingState::STATUS_PAID) {
            return false;
        }

        if ($booking->isMultiSlot()) {
            return false;
        }

        $tenant = $booking->tenant;
        if (! $tenant) {
            return false;
        }

        $rescheduleBeforeHours = $tenant->reschedule_before_hours ?? 24;
        $bookingDateStr = $booking->tanggalbooking instanceof Carbon
            ? $booking->tanggalbooking->toDateString()
            : Carbon::parse($booking->tanggalbooking)->toDateString();
        $bookingDateTime = Carbon::parse($bookingDateStr.' '.$booking->jam);

        return now()->addHours($rescheduleBeforeHours)->lessThan($bookingDateTime);
    }
}
