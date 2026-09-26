<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

use App\Models\Schedule;
use App\Models\Service;

class ScheduleConflictRules
{
    /**
     * Check if a schedule slot conflicts (overlaps) with existing schedule slots for the same tenant and service.
     */
    public static function hasScheduleConflict(
        int $tenantId,
        int $serviceId,
        string $date,
        string $jamMulai,
        string $jamSelesai,
        ?int $excludeScheduleId = null
    ): bool {
        return Schedule::withoutGlobalScopes()
            ->where('idtenant', $tenantId)
            ->where('idlayanan', $serviceId)
            ->whereDate('tanggal', $date)
            ->where(function ($query) use ($jamMulai, $jamSelesai) {
                $query->where('jam_mulai', '<=', $jamSelesai)
                      ->where('jam_selesai', '>=', $jamMulai);
            })
            ->when($excludeScheduleId !== null, fn($q) => $q->where('id', '!=', $excludeScheduleId))
            ->exists();
    }

    /**
     * Check if two time ranges overlap.
     */
    public static function hasTimeOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        return $startA <= $endB && $endA >= $startB;
    }

    /**
     * Check if a schedule slot can safely be deleted (no active non-cancelled bookings).
     */
    public static function canDeleteSchedule(Schedule $schedule): bool
    {
        return !\App\Models\Booking::withoutGlobalScopes()
            ->where('idschedule', $schedule->id)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    /**
     * Calculate weekend price override based on tenant configuration.
     */
    public static function calculateWeekendPrice(
        Service $service,
        ?string $weekendPriceType,
        ?float $weekendPriceValue
    ): ?float {
        if ($weekendPriceType === 'multiplier' && $weekendPriceValue) {
            return (float) ($service->harga * $weekendPriceValue);
        }

        if ($weekendPriceType === 'fixed' && $weekendPriceValue) {
            return (float) $weekendPriceValue;
        }

        return null;
    }
}
