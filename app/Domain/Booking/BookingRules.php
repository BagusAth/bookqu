<?php

namespace App\Domain\Booking;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
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

            $prevStart = Carbon::parse($date . ' ' . $prev->jam_mulai, $timezone);
            $currStart = Carbon::parse($date . ' ' . $curr->jam_mulai, $timezone);

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
            'total'       => $total,
        ];
    }

    /**
     * Check if a slot is already occupied by an active booking.
     */
    public static function isSlotOccupied(int $scheduleId, ?int $excludeBookingId = null): bool
    {
        return DB::table('bookings')
            ->where('idschedule', $scheduleId)
            ->when($excludeBookingId !== null, fn($q) => $q->where('id', '!=', $excludeBookingId))
            ->where(function ($q) {
                $q->whereIn('status', [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED])
                  ->orWhere(function ($sub) {
                      $sub->where('status', BookingState::STATUS_PENDING)
                          ->where('created_at', '>=', now()->subMinutes(15));
                  });
            })
            ->exists();
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
        if (!$tenant) {
            return false;
        }

        $cancelBeforeHours = $tenant->cancel_before_hours ?? 24;
        $bookingDateStr = $booking->tanggalbooking instanceof Carbon
            ? $booking->tanggalbooking->toDateString()
            : Carbon::parse($booking->tanggalbooking)->toDateString();
        $bookingDateTime = Carbon::parse($bookingDateStr . ' ' . $booking->jam);

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
        if (!$tenant) {
            return false;
        }

        $rescheduleBeforeHours = $tenant->reschedule_before_hours ?? 24;
        $bookingDateStr = $booking->tanggalbooking instanceof Carbon
            ? $booking->tanggalbooking->toDateString()
            : Carbon::parse($booking->tanggalbooking)->toDateString();
        $bookingDateTime = Carbon::parse($bookingDateStr . ' ' . $booking->jam);

        return now()->addHours($rescheduleBeforeHours)->lessThan($bookingDateTime);
    }
}
