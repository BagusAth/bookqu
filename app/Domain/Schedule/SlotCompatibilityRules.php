<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

use App\Domain\Booking\BookingTimeRange;
use App\Models\Schedule;
use Carbon\Carbon;
use DateTimeInterface;

class SlotCompatibilityRules
{
    /**
     * Validate that multi-slot schedules are contiguous without any gaps; the rule lives in
     * BookingTimeRange (BQ-114 decision 4).
     * Assumes schedules are already sorted by jam_mulai ascending.
     *
     * @param  array<int, Schedule|array{jam_mulai: string, jam_selesai: string}|object>  $orderedSchedules
     */
    public static function validateContiguousSlots(array $orderedSchedules): bool
    {
        return BookingTimeRange::areContiguous($orderedSchedules);
    }

    /**
     * Check if a set of schedules are compatible (belong to same tenant, same service, and same date).
     *
     * @param  array<int, Schedule|array|object>  $schedules
     */
    public static function areSlotsCompatible(
        array $schedules,
        int $tenantId,
        int $serviceId,
        string $date
    ): bool {
        if (empty($schedules)) {
            return false;
        }

        foreach ($schedules as $sched) {
            $schedObj = is_array($sched) ? (object) $sched : $sched;
            $schedDate = $schedObj->tanggal instanceof Carbon
                ? $schedObj->tanggal->toDateString()
                : Carbon::parse($schedObj->tanggal)->toDateString();

            if ((int) $schedObj->idtenant !== $tenantId) {
                return false;
            }

            if ((int) $schedObj->idlayanan !== $serviceId) {
                return false;
            }

            if ($schedDate !== $date) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if all given schedules are currently available.
     *
     * @param  iterable<Schedule>  $schedules
     */
    public static function areAllSlotsAvailable(iterable $schedules, ?DateTimeInterface $now = null): bool
    {
        foreach ($schedules as $schedule) {
            if (! $schedule instanceof Schedule) {
                return false;
            }

            if (! AvailabilityRules::isSlotAvailable($schedule, $now)) {
                return false;
            }
        }

        return true;
    }
}
