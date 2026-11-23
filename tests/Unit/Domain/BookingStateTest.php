<?php

namespace Tests\Unit\Domain;

use App\Domain\Booking\BookingState;
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
        $this->assertFalse(BookingState::canTransition(BookingState::STATUS_PAID, BookingState::STATUS_PENDING));
    }

    public function test_terminal_states_cannot_transition(): void
    {
        $this->assertTrue(BookingState::isTerminal(BookingState::STATUS_CANCELLED));
        $this->assertTrue(BookingState::isTerminal(BookingState::STATUS_COMPLETED));

        $this->assertFalse(BookingState::canTransition(BookingState::STATUS_CANCELLED, BookingState::STATUS_PAID));
        $this->assertFalse(BookingState::canTransition(BookingState::STATUS_COMPLETED, BookingState::STATUS_PAID));
    }

    /**
     * Labels from the approved owner-portal contract (owner-portal.md §8, BQ-129).
     */
    public function test_format_label_provides_indonesian_description(): void
    {
        $this->assertSame('Menunggu bayar', BookingState::formatLabel(BookingState::STATUS_PENDING));
        $this->assertSame('Terkonfirmasi', BookingState::formatLabel(BookingState::STATUS_PAID));
        $this->assertSame('Selesai', BookingState::formatLabel(BookingState::STATUS_COMPLETED));
        $this->assertSame('Dibatalkan', BookingState::formatLabel(BookingState::STATUS_CANCELLED));
        $this->assertSame('Tidak datang', BookingState::formatLabel('no_show'));
    }
}
