<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Booking\BookingTimeRange;
use PHPUnit\Framework\TestCase;

/**
 * BQ-114 decision: a booking's time range comes from its stored slots, never from the
 * service's current duration.
 */
class BookingTimeRangeTest extends TestCase
{
    public function test_an_inclusive_slot_end_is_shown_one_minute_later(): void
    {
        $range = BookingTimeRange::forSlot('2026-11-20', '14:00:00', '14:59:00');

        $this->assertSame('14:00', $range->startTime());
        $this->assertSame('15:00', $range->endTime());
        $this->assertSame(60, $range->durationMinutes());
        $this->assertSame('14:00 – 15:00', $range->label());
        $this->assertSame('20261120T140000/20261120T150000', $range->googleCalendarDates());
    }

    public function test_an_exact_slot_end_is_shown_as_stored(): void
    {
        $range = BookingTimeRange::forSlot('2026-11-20', '10:00:00', '11:00:00');

        $this->assertSame('11:00', $range->endTime());
        $this->assertSame(60, $range->durationMinutes());
    }

    public function test_an_inclusive_end_at_midnight_moves_to_the_next_day(): void
    {
        $range = BookingTimeRange::forSlot('2026-11-20', '23:00:00', '23:59:00');

        $this->assertSame('00:00', $range->endTime());
        $this->assertSame('20261120T230000/20261121T000000', $range->googleCalendarDates());
    }

    public function test_a_group_spans_its_first_start_to_its_last_end(): void
    {
        $range = BookingTimeRange::forSlots('2026-11-20', [
            ['jam_mulai' => '15:00:00', 'jam_selesai' => '15:59:00'],
            ['jam_mulai' => '14:00:00', 'jam_selesai' => '14:59:00'],
        ]);

        $this->assertSame('14:00 – 16:00', $range->label());
        $this->assertSame(120, $range->durationMinutes());
    }

    public function test_a_booking_without_a_slot_shows_its_start_only(): void
    {
        $range = BookingTimeRange::withoutSlot('2026-11-20', '14:00:00');

        $this->assertSame('14:00', $range->startTime());
        $this->assertNull($range->endTime());
        $this->assertNull($range->durationMinutes());
        $this->assertSame('14:00', $range->label());
        $this->assertSame('20261120T140000/20261120T140000', $range->googleCalendarDates());
    }

    public function test_a_group_with_a_missing_slot_has_no_end(): void
    {
        $range = BookingTimeRange::forSlots('2026-11-20', [
            ['jam_mulai' => '14:00:00', 'jam_selesai' => '14:59:00'],
            null,
        ], fallbackStart: '15:00:00');

        $this->assertNull($range->endTime());
    }

    public function test_slots_are_contiguous_when_each_starts_at_the_previous_end(): void
    {
        $this->assertTrue(BookingTimeRange::areContiguous([
            ['jam_mulai' => '14:00:00', 'jam_selesai' => '14:59:00'],
            ['jam_mulai' => '15:00:00', 'jam_selesai' => '15:59:00'],
        ]));
        $this->assertTrue(BookingTimeRange::areContiguous([
            (object) ['jam_mulai' => '10:00:00', 'jam_selesai' => '11:00:00'],
            (object) ['jam_mulai' => '11:00:00', 'jam_selesai' => '12:00:00'],
        ]));
        $this->assertTrue(BookingTimeRange::areContiguous([['jam_mulai' => '09:00:00', 'jam_selesai' => '09:59:00']]));
    }

    public function test_slots_with_a_gap_or_overlap_are_not_contiguous(): void
    {
        $this->assertFalse(BookingTimeRange::areContiguous([
            ['jam_mulai' => '14:00:00', 'jam_selesai' => '14:59:00'],
            ['jam_mulai' => '16:00:00', 'jam_selesai' => '16:59:00'],
        ]));
        $this->assertFalse(BookingTimeRange::areContiguous([
            ['jam_mulai' => '14:00:00', 'jam_selesai' => '15:29:00'],
            ['jam_mulai' => '15:00:00', 'jam_selesai' => '16:29:00'],
        ]));
    }
}
