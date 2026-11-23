<?php

namespace Tests\Unit\Domain;

use App\Domain\Booking\BookingRules;
use Tests\TestCase;

class BookingRulesTest extends TestCase
{
    /**
     * BQ-114 decision 4: contiguity follows the stored slot boundaries.
     */
    public function test_validate_contiguous_slots_accepts_contiguous_hours(): void
    {
        $slots = [
            ['jam_mulai' => '10:00:00', 'jam_selesai' => '10:59:00'],
            ['jam_mulai' => '11:00:00', 'jam_selesai' => '11:59:00'],
            ['jam_mulai' => '12:00:00', 'jam_selesai' => '13:00:00'],
        ];

        $this->assertTrue(BookingRules::validateContiguousSlots($slots));
    }

    public function test_validate_contiguous_slots_rejects_gaps(): void
    {
        $slots = [
            ['jam_mulai' => '10:00:00', 'jam_selesai' => '10:59:00'],
            ['jam_mulai' => '12:00:00', 'jam_selesai' => '12:59:00'],
        ];

        $this->assertFalse(BookingRules::validateContiguousSlots($slots));
    }
}
