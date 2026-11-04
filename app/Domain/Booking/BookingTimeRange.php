<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use Carbon\CarbonImmutable;

/**
 * The time range of a booked session or group (BQ-114, Tech Lead decision 2026-11-04).
 *
 * 1. The range comes from the booked slots: the first slot's `jam_mulai` to the last slot's
 *    end. The service's current duration is never used for an existing booking.
 * 2. Slot intervals are multiples of 5 minutes. A stored end whose length leaves 4 minutes
 *    over a multiple of 5 is inclusive (14:00–14:59) and ends one minute later; otherwise the
 *    stored end is the end.
 * 3. A booking without a slot has a start and no end; no duration is invented.
 */
final class BookingTimeRange
{
    private const TIMEZONE = 'Asia/Jakarta';

    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly ?CarbonImmutable $end,
    ) {}

    public static function forSlot(string $date, string $jamMulai, string $jamSelesai): self
    {
        $start = self::at($date, $jamMulai);

        return new self($start, self::slotEnd($start, $date, $jamMulai, $jamSelesai));
    }

    /**
     * The range of a group of slots on one date. A missing slot (null) leaves the group
     * without an end; `$fallbackStart` is used when no slot is present at all.
     *
     * @param  iterable<array{jam_mulai: string, jam_selesai: string}|object|null>  $slots
     */
    public static function forSlots(string $date, iterable $slots, ?string $fallbackStart = null): self
    {
        $ranges = [];
        $missing = false;
        foreach ($slots as $slot) {
            if ($slot === null) {
                $missing = true;

                continue;
            }
            $ranges[] = self::forSlot($date, self::field($slot, 'jam_mulai'), self::field($slot, 'jam_selesai'));
        }

        if ($ranges === []) {
            return self::withoutSlot($date, $fallbackStart ?? '00:00:00');
        }

        usort($ranges, fn (self $a, self $b): int => $a->start <=> $b->start);
        $last = end($ranges);

        return new self($ranges[0]->start, $missing ? null : $last->end);
    }

    public static function withoutSlot(string $date, string $jam): self
    {
        return new self(self::at($date, $jam), null);
    }

    /**
     * Slots are contiguous when each one starts exactly where the previous one ends (decision 4).
     *
     * @param  array<int, array{jam_mulai: string, jam_selesai: string}|object>  $orderedSlots
     */
    public static function areContiguous(array $orderedSlots, string $date = '2000-01-01'): bool
    {
        $slots = array_values($orderedSlots);
        for ($i = 1; $i < count($slots); $i++) {
            $previous = self::forSlot($date, self::field($slots[$i - 1], 'jam_mulai'), self::field($slots[$i - 1], 'jam_selesai'));
            $current = self::at($date, self::field($slots[$i], 'jam_mulai'));

            if ($previous->end === null || ! $previous->end->equalTo($current)) {
                return false;
            }
        }

        return true;
    }

    public function startTime(): string
    {
        return $this->start->format('H:i');
    }

    public function endTime(): ?string
    {
        return $this->end?->format('H:i');
    }

    public function durationMinutes(): ?int
    {
        return $this->end === null ? null : (int) $this->start->diffInMinutes($this->end);
    }

    public function label(string $separator = ' – '): string
    {
        return $this->end === null ? $this->startTime() : $this->startTime().$separator.$this->endTime();
    }

    /**
     * Google Calendar `dates` value; without an end the event starts and ends at the start.
     */
    public function googleCalendarDates(): string
    {
        return $this->start->format('Ymd\THis').'/'.($this->end ?? $this->start)->format('Ymd\THis');
    }

    private static function slotEnd(CarbonImmutable $start, string $date, string $jamMulai, string $jamSelesai): CarbonImmutable
    {
        $end = self::at($date, $jamSelesai);
        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        $length = (int) $start->diffInMinutes($end);

        return $length % 5 === 4 ? $end->addMinute() : $end;
    }

    private static function at(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse(substr($date, 0, 10).' '.substr($time, 0, 8), self::TIMEZONE)->startOfMinute();
    }

    /**
     * @param  array<string, mixed>|object  $slot
     */
    private static function field(array|object $slot, string $name): string
    {
        return (string) (is_array($slot) ? ($slot[$name] ?? '') : ($slot->{$name} ?? ''));
    }
}
