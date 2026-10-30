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
    public const STATUS_AVAILABLE = 'AVAILABLE';

    public const STATUS_BOOKED = 'BOOKED';

    public const STATUS_BLOCKED = 'BLOCKED';

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
     * Derived from refusal(), the one rule shared by listings and locked claims.
     */
    public static function getSlotAvailabilityStatus(
        Schedule $schedule,
        ?DateTimeInterface $now = null,
        ?int $excludeBookingId = null
    ): string {
        return self::refusal($schedule, $now, $excludeBookingId)?->availabilityStatus() ?? self::STATUS_AVAILABLE;
    }

    /**
     * Why a schedule slot cannot be booked now, or null when it can (ADR-0005 decision 1).
     * Checks, in order: ownership and requested date (when given), slot status, closed
     * date, past time, service and fulfillment eligibility, occupancy.
     */
    public static function refusal(
        Schedule $schedule,
        ?DateTimeInterface $now = null,
        ?int $excludeBookingId = null,
        ?int $tenantId = null,
        ?int $serviceId = null,
        ?string $date = null,
    ): ?SlotRefusal {
        $now = $now ? Carbon::instance($now) : Carbon::now('Asia/Jakarta');

        if ($tenantId !== null && (int) $schedule->idtenant !== $tenantId) {
            return SlotRefusal::ForeignTenant;
        }

        if ($serviceId !== null && (int) $schedule->idlayanan !== $serviceId) {
            return SlotRefusal::ForeignService;
        }

        $slotDate = Carbon::parse($schedule->tanggal, 'Asia/Jakarta')->toDateString();

        if ($date !== null && $slotDate !== $date) {
            return SlotRefusal::WrongDate;
        }

        if ($schedule->status === 'diblokir') {
            return SlotRefusal::Blocked;
        }

        if ($schedule->status !== 'tersedia') {
            return SlotRefusal::NotOpen;
        }

        if (self::isDateBlocked((int) $schedule->idtenant, $slotDate)) {
            return SlotRefusal::DateClosed;
        }

        if (Carbon::parse($slotDate.' '.$schedule->jam_mulai, 'Asia/Jakarta')->lessThanOrEqualTo($now)) {
            return SlotRefusal::Past;
        }

        $service = $schedule->relationLoaded('layanan') && $schedule->layanan
            ? $schedule->layanan
            // scope-bypass: the schedule's own service, read for eligibility only.
            : Service::withoutGlobalScopes()->find($schedule->idlayanan);

        if ($service && (! $service->is_active || ! $service->hasActiveFulfillment())) {
            return SlotRefusal::ServiceUnavailable;
        }

        if (BookingRules::isSlotOccupied((int) $schedule->id, $excludeBookingId)) {
            return SlotRefusal::Occupied;
        }

        return null;
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
     * Check if a schedule time has already passed for today.
     */
    public static function isSlotPast(string $date, string $time, string $timezone = 'Asia/Jakarta'): bool
    {
        $now = Carbon::now($timezone);
        $slotDateTime = Carbon::parse($date.' '.$time, $timezone);

        return $slotDateTime->lessThanOrEqualTo($now);
    }
}
