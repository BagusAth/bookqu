<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

use App\Models\Schedule;
use Carbon\Carbon;
use DateTimeInterface;

class SlotCompatibilityRules
{
    /**
     * Validate that multi-slot schedules are contiguous without any gaps.
     * Assumes schedules are already sorted by jam_mulai ascending.
     *
     * @param array<int, Schedule|array|object> $orderedSchedules
     * @param int $durationMinutes
     * @param string $date
     * @param string $timezone
     * @return bool
     */
    public static function validateContiguousSlots(
        array $orderedSchedules,
        int $durationMinutes,
        string $date,
        string $timezone = 'Asia/Jakarta'
    ): bool {
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
     * Check if a set of schedules are compatible (belong to same tenant, same service, and same date).
     *
     * @param array<int, Schedule|array|object> $schedules
     * @param int $tenantId
     * @param int $serviceId
     * @param string $date
     * @return bool
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
     * @param iterable<Schedule> $schedules
     * @param DateTimeInterface|null $now
     * @return bool
     */
    public static function areAllSlotsAvailable(iterable $schedules, ?DateTimeInterface $now = null): bool
    {
        foreach ($schedules as $schedule) {
            if (!$schedule instanceof Schedule) {
                return false;
            }

            if (!AvailabilityRules::isSlotAvailable($schedule, $now)) {
                return false;
            }
        }

        return true;
    }
}
