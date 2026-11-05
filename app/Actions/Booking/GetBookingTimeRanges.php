<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Domain\Booking\BookingTimeRange;
use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Support\Carbon;

/**
 * Prepares the time ranges customer pages and mails render for existing bookings (BQ-114):
 * `timeRange` for the whole booking or payment group, `sessionRanges` per booking id.
 */
final class GetBookingTimeRanges
{
    /**
     * @param  iterable<Booking>  $bookings  bookings the caller has already authorized
     * @return array{timeRange: BookingTimeRange, sessionRanges: array<int, BookingTimeRange>}
     */
    public function execute(iterable $bookings): array
    {
        $bookings = collect($bookings)->filter()->values();

        // scope-bypass: the slots of bookings the caller already authorized, filtered to each booking's tenant.
        $slots = Schedule::withoutGlobalScopes()
            ->whereIn('id', $bookings->pluck('idschedule')->filter()->unique()->all())
            ->get(['id', 'idtenant', 'jam_mulai', 'jam_selesai'])
            ->keyBy('id');

        $sessionRanges = [];
        $groupSlots = [];
        $date = null;
        $fallbackStart = null;

        foreach ($bookings as $booking) {
            $bookingDate = self::dateOf($booking);
            $date ??= $bookingDate;
            $fallbackStart ??= (string) $booking->jam;

            $slot = $slots->get((int) $booking->idschedule);
            if ($slot !== null && (int) $slot->idtenant !== (int) $booking->idtenant) {
                $slot = null;
            }

            $sessionRanges[(int) $booking->id] = $slot !== null
                ? BookingTimeRange::forSlot($bookingDate, (string) $slot->jam_mulai, (string) $slot->jam_selesai)
                : BookingTimeRange::withoutSlot($bookingDate, (string) $booking->jam);
            $groupSlots[] = $slot;
        }

        $date ??= Carbon::now()->toDateString();

        return [
            'timeRange' => BookingTimeRange::forSlots($date, $groupSlots, $fallbackStart),
            'sessionRanges' => $sessionRanges,
        ];
    }

    private static function dateOf(Booking $booking): string
    {
        return Carbon::parse((string) $booking->tanggalbooking)->toDateString();
    }
}
