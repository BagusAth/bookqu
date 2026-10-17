<?php

namespace Tests\Unit\Domain;

use App\Domain\Booking\BookingRules;
use App\Models\Service;
use Tests\TestCase;

class BookingRulesTest extends TestCase
{
    public function test_validate_contiguous_slots_accepts_contiguous_hours(): void
    {
        $date = '2026-10-01';
        $duration = 60;

        $slots = [
            ['jam_mulai' => '10:00:00'],
            ['jam_mulai' => '11:00:00'],
            ['jam_mulai' => '12:00:00'],
        ];

        $this->assertTrue(BookingRules::validateContiguousSlots($slots, $duration, $date));
    }

    public function test_validate_contiguous_slots_rejects_gaps(): void
    {
        $date = '2026-10-01';
        $duration = 60;

        $slots = [
            ['jam_mulai' => '10:00:00'],
            ['jam_mulai' => '12:00:00'],
        ];

        $this->assertFalse(BookingRules::validateContiguousSlots($slots, $duration, $date));
    }

    public function test_calculate_slot_prices_with_overrides(): void
    {
        $service = new Service(['harga' => 100000]);

        $schedules = [
            (object) ['harga_override' => null],
            (object) ['harga_override' => 125000],
            (object) ['harga_override' => 0],
        ];

        $pricing = BookingRules::calculateSlotPrices($schedules, $service);

        $this->assertSame([100000.0, 125000.0, 0.0], $pricing['slot_prices']);
        $this->assertSame(225000.0, $pricing['total']);
    }
}
