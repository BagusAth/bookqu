<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use App\Models\Schedule;
use Carbon\CarbonImmutable;

final class UnitUtilisation
{
    /**
     * @param  iterable<Schedule>  $slots  with the occupied seat count projected as seats_taken
     * @return array{offeredMinutes: int, usedMinutes: int, rate: float}
     */
    public static function forSlots(iterable $slots): array
    {
        $offered = [];
        $used = [];
        foreach ($slots as $slot) {
            $date = CarbonImmutable::parse($slot->tanggal)->toDateString();
            $range = BookingTimeRange::forSlot($date, (string) $slot->jam_mulai, (string) $slot->jam_selesai);
            if ($range->end === null) {
                continue;
            }

            $interval = [$range->start->getTimestamp(), $range->end->getTimestamp()];
            if ((int) $slot->getAttribute('seats_taken') > 0) {
                $used[] = $interval;
                $offered[] = $interval;
            } elseif ($slot->status === 'tersedia') {
                $offered[] = $interval;
            }
        }
        $offeredMinutes = self::coveredMinutes($offered);
        $usedMinutes = self::coveredMinutes($used);

        return [
            'offeredMinutes' => $offeredMinutes,
            'usedMinutes' => $usedMinutes,
            'rate' => $offeredMinutes > 0 ? round($usedMinutes / $offeredMinutes * 100, 1) : 0.0,
        ];
    }

    /** @param list<array{int, int}> $intervals */
    private static function coveredMinutes(array $intervals): int
    {
        sort($intervals);
        $endOfCoverage = 0;
        $seconds = 0;
        foreach ($intervals as [$start, $end]) {
            $seconds += max(0, $end - max($start, $endOfCoverage));
            $endOfCoverage = max($end, $endOfCoverage);
        }

        return intdiv($seconds, 60);
    }
}
