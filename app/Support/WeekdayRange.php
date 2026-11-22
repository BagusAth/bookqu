<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Short owner-portal label for a set of ISO weekdays (1 = Monday … 7 = Sunday), used by peak-hour
 * rules and quiet-hour promos: "Setiap hari", "Sen–Jum", "Sab, Min", "Sen, Rab".
 */
final class WeekdayRange
{
    private const NAMES = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];

    /** @param  array<int, int|string>  $days */
    public static function label(array $days): string
    {
        $days = collect($days)->map(fn ($day): int => (int) $day)->filter(fn (int $day): bool => isset(self::NAMES[$day]))->unique()->sort()->values();
        if ($days->count() === 7) {
            return 'Setiap hari';
        }

        $groups = [];
        foreach ($days as $day) {
            $last = array_key_last($groups);
            if ($last !== null && end($groups[$last]) === $day - 1) {
                $groups[$last][] = $day;
            } else {
                $groups[] = [$day];
            }
        }

        return collect($groups)->map(fn (array $group): string => count($group) > 2
            ? self::NAMES[$group[0]].'–'.self::NAMES[end($group)]
            : collect($group)->map(fn (int $day): string => self::NAMES[$day])->join(', '))->join(', ');
    }
}
