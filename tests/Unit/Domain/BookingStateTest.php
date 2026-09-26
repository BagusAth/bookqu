<?php

namespace Tests\Unit\Domain;

use App\Domain\Booking\BookingState;
use Carbon\Carbon;
use Tests\TestCase;

class BookingStateTest extends TestCase
{
    public function test_can_transition_from_pending_to_valid_target_statuses(): void
    {
        $this->assertTrue(BookingState::canTransition(BookingState::STATUS_PENDING, BookingState::STATUS_PAID));
        $this->assertTrue(BookingState::canTransition(BookingState::STATUS_PENDING, BookingState::STATUS_COMPLETED));
        $this->assertTrue(BookingState::canTransition(BookingState::STATUS_PENDING, BookingState::STATUS_CANCELLED));
        $this->assertTrue(BookingState::canTransition(BookingState::STATUS_PENDING, BookingState::STATUS_PENDING));
    }

    public function test_can_transition_from_paid_to_completed_and_cancelled(): void
    {
        $this->assertTrue(BookingState::canTransition(BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED));
        $this->assertTrue(BookingState::canTransition(BookingState::STATUS_PAID, BookingState::STATUS_CANCELLED));
        $this->assertTrue(BookingState::canTransition(BookingState::STATUS_PAID, BookingState::STATUS_REFUNDED));
        $this->assertFalse(BookingState::canTransition(BookingState::STATUS_PAID, BookingState::STATUS_PENDING));
    }

    public function test_terminal_states_cannot_transition(): void
    {
        $this->assertTrue(BookingState::isTerminal(BookingState::STATUS_CANCELLED));
        $this->assertTrue(BookingState::isTerminal(BookingState::STATUS_COMPLETED));
        $this->assertTrue(BookingState::isTerminal(BookingState::STATUS_REFUNDED));

        $this->assertFalse(BookingState::canTransition(BookingState::STATUS_CANCELLED, BookingState::STATUS_PAID));
        $this->assertFalse(BookingState::canTransition(BookingState::STATUS_COMPLETED, BookingState::STATUS_PAID));
    }

    public function test_occupies_slot_evaluates_correctly(): void
    {
        $this->assertTrue(BookingState::occupiesSlot(BookingState::STATUS_PAID));
        $this->assertTrue(BookingState::occupiesSlot(BookingState::STATUS_COMPLETED));
        $this->assertFalse(BookingState::occupiesSlot(BookingState::STATUS_CANCELLED));
        $this->assertFalse(BookingState::occupiesSlot(BookingState::STATUS_REFUNDED));

        // Pending within 15 min grace window
        $recent = Carbon::now()->subMinutes(5);
        $this->assertTrue(BookingState::occupiesSlot(BookingState::STATUS_PENDING, $recent, 15));

        // Pending expired > 15 min
        $expired = Carbon::now()->subMinutes(20);
        $this->assertFalse(BookingState::occupiesSlot(BookingState::STATUS_PENDING, $expired, 15));
    }

    public function test_format_label_provides_indonesian_description(): void
    {
        $this->assertSame('lunas / dikonfirmasi', BookingState::formatLabel(BookingState::STATUS_PAID));
        $this->assertSame('selesai', BookingState::formatLabel(BookingState::STATUS_COMPLETED));
        $this->assertSame('dibatalkan', BookingState::formatLabel(BookingState::STATUS_CANCELLED));
    }
}
