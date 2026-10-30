<?php

namespace Tests\Feature\Database;

use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The `unique_active_booking_slot` index is the last line of defence against
 * double booking when two requests pass the occupancy check together (INV-BOOK-05).
 */
class ActiveBookingSlotIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_second_active_booking_for_same_schedule(): void
    {
        $schedule = Schedule::factory()->create();
        $this->bookingFor($schedule, 'paid');

        $this->expectException(UniqueConstraintViolationException::class);

        $this->bookingFor($schedule, 'pending');
    }

    public function test_cancelled_booking_does_not_block_new_active_booking_for_same_schedule(): void
    {
        $schedule = Schedule::factory()->create();
        $this->bookingFor($schedule, 'cancelled');

        $booking = $this->bookingFor($schedule, 'pending');

        $this->assertModelExists($booking);
    }

    private function bookingFor(Schedule $schedule, string $status): Booking
    {
        return Booking::factory()->create([
            'idtenant' => $schedule->idtenant,
            'idlayanan' => $schedule->idlayanan,
            'idschedule' => $schedule->id,
            'status' => $status,
        ]);
    }
}
