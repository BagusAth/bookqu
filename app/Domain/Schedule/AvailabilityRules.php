<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

use App\Domain\Booking\BookingRules;
use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;
use DateTimeInterface;

class AvailabilityRules
{
    public const STATUS_AVAILABLE   = 'AVAILABLE';
    public const STATUS_BOOKED      = 'BOOKED';
    public const STATUS_BLOCKED     = 'BLOCKED';
    public const STATUS_UNAVAILABLE = 'UNAVAILABLE';

    /**
     * Check if a schedule slot is currently available for booking.
     */
    public static function isSlotAvailable(
        Schedule $schedule,
        ?DateTimeInterface $now = null,
        ?int $excludeBookingId = null
    ): bool {
        return self::getSlotAvailabilityStatus($schedule, $now, $excludeBookingId) === self::STATUS_AVAILABLE;
    }

    /**
     * Determine slot availability status: AVAILABLE, BOOKED, BLOCKED, or UNAVAILABLE.
     */
    public static function getSlotAvailabilityStatus(
        Schedule $schedule,
        ?DateTimeInterface $now = null,
        ?int $excludeBookingId = null
    ): string {
        $now = $now ? Carbon::instance($now) : Carbon::now('Asia/Jakarta');

        // 1. Slot is explicitly blocked
        if ($schedule->status === 'diblokir') {
            return self::STATUS_BLOCKED;
        }

        // 2. Slot is not marked 'tersedia'
        if ($schedule->status !== 'tersedia') {
            return self::STATUS_UNAVAILABLE;
        }

        $slotDate = $schedule->tanggal instanceof Carbon
            ? $schedule->tanggal->toDateString()
            : Carbon::parse($schedule->tanggal)->toDateString();

        // 3. Date is blocked by owner
        if (self::isDateBlocked((int) $schedule->idtenant, $slotDate)) {
            return self::STATUS_BLOCKED;
        }

        // 4. Date/time has already passed
        $slotDateTime = Carbon::parse($slotDate . ' ' . $schedule->jam_mulai, 'Asia/Jakarta');
        if ($slotDateTime->lessThanOrEqualTo($now)) {
            return self::STATUS_UNAVAILABLE;
        }

        // 5. Service is inactive or has inactive staff/resource fulfillment
        $service = $schedule->relationLoaded('layanan') && $schedule->layanan
            ? $schedule->layanan
            : Service::withoutGlobalScopes()->find($schedule->idlayanan);

        if ($service && (!$service->is_active || !$service->hasActiveFulfillment())) {
            return self::STATUS_UNAVAILABLE;
        }

        // 6. Slot is already booked by an active booking
        if (BookingRules::isSlotOccupied((int) $schedule->id, $excludeBookingId)) {
            return self::STATUS_BOOKED;
        }

        return self::STATUS_AVAILABLE;
    }

    /**
     * Check if a specific date is blocked by the owner.
     */
    public static function isDateBlocked(int $tenantId, string $date): bool
    {
        return OwnerBlockedDate::withoutGlobalScopes()
            ->where('idtenant', $tenantId)
            ->whereDate('tanggal', $date)
            ->exists();
    }

    /**
     * Check if a pending booking is still holding the slot within the grace window.
     */
    public static function isPendingBookingHoldingSlot(?DateTimeInterface $createdAt, int $graceMinutes = BookingRules::PENDING_GRACE_MINUTES): bool
    {
        if ($createdAt === null) {
            return false;
        }

        return Carbon::instance($createdAt)->greaterThanOrEqualTo(now()->subMinutes($graceMinutes));
    }

    /**
     * Check if a schedule time has already passed for today.
     */
    public static function isSlotPast(string $date, string $time, string $timezone = 'Asia/Jakarta'): bool
    {
        $now = Carbon::now($timezone);
        $slotDateTime = Carbon::parse($date . ' ' . $time, $timezone);

        return $slotDateTime->lessThanOrEqualTo($now);
    }
}
