<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

use App\Domain\Booking\BookingRules;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

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
        // scope-bypass: filtered by the given tenant id or an already-resolved schedule id
        return Schedule::withoutGlobalScopes()
            ->where('idtenant', $tenantId)
            ->where('idlayanan', $serviceId)
            ->whereDate('tanggal', $date)
            ->where(function ($query) use ($jamMulai, $jamSelesai) {
                $query->where('jam_mulai', '<=', $jamSelesai)
                    ->where('jam_selesai', '>=', $jamMulai);
            })
            ->when($excludeScheduleId !== null, fn ($q) => $q->where('id', '!=', $excludeScheduleId))
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
     * Start and end times of the service's existing slots on one date.
     *
     * @return list<array{jam_mulai: string, jam_selesai: string}>
     */
    public static function slotTimesOnDate(int $tenantId, int $serviceId, string $date): array
    {
        // scope-bypass: filtered by the given tenant id
        return Schedule::withoutGlobalScopes()
            ->where('idtenant', $tenantId)
            ->where('idlayanan', $serviceId)
            ->whereDate('tanggal', $date)
            ->get(['jam_mulai', 'jam_selesai'])
            ->map(fn (Schedule $slot): array => [
                'jam_mulai' => (string) $slot->jam_mulai,
                'jam_selesai' => (string) $slot->jam_selesai,
            ])
            ->values()
            ->all();
    }

    /**
     * Whether a time range overlaps any of the given slots, with the same bounds as hasScheduleConflict().
     *
     * @param  list<array{jam_mulai: string, jam_selesai: string}>  $slots
     */
    public static function overlapsAny(array $slots, string $jamMulai, string $jamSelesai): bool
    {
        foreach ($slots as $slot) {
            if (self::hasTimeOverlap($slot['jam_mulai'], $slot['jam_selesai'], $jamMulai, $jamSelesai)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bookings of the tenant on a date that still occupy their slot under the single
     * occupancy rule (INV-BOOK-03). A date with any of them cannot be closed (BQ-108).
     *
     * @return Collection<int, Booking>
     */
    public static function activeBookingsOnDate(int $tenantId, string $date): Collection
    {
        // scope-bypass: filtered by the given, server-resolved tenant id
        return Booking::withoutGlobalScopes()
            ->with(['layanan' => fn ($query) => $query->withoutGlobalScopes()])
            ->where('bookings.idtenant', $tenantId)
            ->whereDate('bookings.tanggalbooking', $date)
            ->where(fn ($query) => BookingRules::applyOccupiesSlotCondition($query, 'bookings.'))
            ->orderBy('bookings.jam')
            ->orderBy('bookings.id')
            ->get();
    }

    /**
     * A slot can be deleted when no booking occupies it under the single occupancy rule
     * (INV-BOOK-03): cancelled bookings and pending bookings past their payment deadline
     * plus settlement margin do not block it.
     */
    public static function canDeleteSchedule(Schedule $schedule): bool
    {
        return ! BookingRules::isSlotOccupied((int) $schedule->id);
    }
}
