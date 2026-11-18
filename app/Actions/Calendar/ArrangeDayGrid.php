<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Domain\Booking\BookingState;

final class ArrangeDayGrid
{
    private const MAX_COLUMNS = 6;

    /**
     * @param  list<array{id: int, name: string}>  $options
     * @param  list<array<string, mixed>>  $slots
     * @param  list<int>  $chosen
     * @return array<string, mixed>
     */
    public function execute(array $options, array $slots, array $chosen, ?string $now): array
    {
        $slotsByColumn = collect($slots)->groupBy(fn (array $slot): int => (int) ($slot['column_id'] ?? $slot['service_id']));
        $offered = collect($options)->keyBy('id');
        $nowMinute = $now === null ? null : (int) substr($now, 0, 2) * 60 + (int) substr($now, 3, 2);
        $priorities = $slotsByColumn->map(fn ($column): int => $column->max(static function (array $slot) use ($nowMinute): int {
            if ($slot['bookings'] === []) {
                return 0;
            }

            $isRunning = $nowMinute !== null && $slot['startMinute'] <= $nowMinute && $nowMinute < $slot['endMinute']
                && array_any($slot['bookings'], fn (array $booking): bool => $booking['status'] === BookingState::STATUS_PAID);

            return $isRunning ? 2 : 1;
        }));
        $defaults = $offered->intersectByKeys($priorities)
            ->sortByDesc(fn (array $option): int => $priorities[$option['id']])->keys()->all();
        $selected = array_slice(array_values(array_intersect($chosen ?: $defaults, $offered->keys()->all())), 0, self::MAX_COLUMNS);
        $visible = $slotsByColumn->only($selected)->flatten(1);
        $start = $visible->isEmpty() ? 8 * 60 : intdiv((int) $visible->min('startMinute'), 60) * 60;
        $end = $visible->isEmpty() ? 22 * 60 : (int) ceil($visible->max('endMinute') / 60) * 60;
        $hours = array_map(fn (int $minute): array => [
            'label' => sprintf('%02d.00', intdiv($minute, 60)),
            'offset' => $minute - $start,
        ], range($start, max($start, $end - 60), 60));

        $columns = [];
        foreach ($selected as $id) {
            $track = [];
            $ends = [];
            foreach ($slotsByColumn->get($id, collect())->sortBy('startMinute') as $slot) {
                $lane = 0;
                while (isset($ends[$lane]) && $ends[$lane] > $slot['startMinute']) {
                    $lane++;
                }
                $ends[$lane] = max($slot['endMinute'], $slot['startMinute'] + 25);
                $track[] = $slot + [
                    'offset' => $slot['startMinute'] - $start,
                    'length' => $slot['endMinute'] - $slot['startMinute'],
                    'lane' => $lane,
                ];
            }
            $columns[] = ['name' => $offered[$id]['name'], 'slots' => $track, 'lanes' => max(1, count($ends))];
        }

        return [
            'columns' => $columns,
            'options' => $options,
            'selected' => $selected,
            'hours' => $hours,
            'minutes' => max(60, $end - $start),
            'nowOffset' => $nowMinute !== null && $nowMinute >= $start && $nowMinute < $end ? $nowMinute - $start : null,
        ];
    }
}
