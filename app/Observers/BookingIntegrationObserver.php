<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domain\Booking\BookingState;
use App\Domain\Integrations\BookingIntegrationEvent;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class BookingIntegrationObserver
{
    public function created(Booking $booking): void
    {
        $this->publish($booking, 'booking.created');
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('status')) {
            $name = match ($booking->status) {
                BookingState::STATUS_PAID => 'booking.paid',
                BookingState::STATUS_CANCELLED => 'booking.cancelled',
                BookingState::STATUS_COMPLETED => 'booking.completed',
                BookingState::STATUS_NO_SHOW => 'booking.no_show',
                default => null,
            };
            if ($name !== null) {
                $this->publish($booking, $name);
            }
        }
        if ($booking->wasChanged(['idschedule', 'tanggalbooking', 'jam'])) {
            $this->publish($booking, 'booking.rescheduled');
        }
    }

    private function publish(Booking $booking, string $name): void
    {
        $event = new BookingIntegrationEvent($name, (int) $booking->idtenant, (int) $booking->id);
        DB::afterCommit(function () use ($event): void {
            try {
                event($event);
            } catch (Throwable) {
                Log::warning('Could not dispatch booking integration event.', ['booking_id' => $event->bookingId, 'event' => $event->name]);
            }
        });
    }
}
