<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Models\Booking;
use Illuminate\Notifications\DatabaseNotification;

final class ResolveNotificationBooking
{
    public function execute(?DatabaseNotification $notification): ?string
    {
        $bookingId = filter_var($notification?->data['booking_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($bookingId === false || ! Booking::whereKey($bookingId)->exists()) {
            return null;
        }

        return route('owner.bookings', ['panel' => $bookingId]);
    }
}
