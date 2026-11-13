<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

/**
 * Keeps a booking's seat rows in step with the booking (ADR-0017 decision 2). The seat index
 * on (`active_idschedule`, `seat_no`) is the final database guard against overbooking a slot;
 * the occupancy and capacity decision itself stays in BookingRules.
 *
 * Called by the Booking model on create and update, so every writer (checkout, walk-in,
 * reschedule, status changes, payment lifecycle) keeps seats correct without extra code.
 */
final class BookingSeats
{
    /**
     * Statuses whose seats count in the seat index. Must equal the CASE list of the
     * `booking_seats.active_idschedule` virtual column (checked by a test).
     */
    public const INDEXED_STATUSES = [
        BookingState::STATUS_PENDING,
        BookingState::STATUS_PAID,
        BookingState::STATUS_COMPLETED,
        BookingState::STATUS_NO_SHOW,
    ];

    /** Seats for a new booking: the lowest free seat numbers of its slot. */
    public static function place(Booking $booking): void
    {
        DB::table('booking_seats')->where('idbooking', $booking->id)->delete();

        $scheduleId = (int) $booking->idschedule;
        DB::table('booking_seats')->insert(array_map(fn (int $seat): array => [
            'idbooking' => $booking->id,
            'idschedule' => $scheduleId,
            'seat_no' => $seat,
            'status' => $booking->status,
        ], self::seatNumbers($scheduleId, max(1, (int) ($booking->peserta ?? 1)))));
    }

    /** After an update: move the seats when the slot or participants changed, else copy the status. */
    public static function sync(Booking $booking): void
    {
        if ($booking->wasChanged('idschedule') || $booking->wasChanged('peserta')) {
            self::place($booking);

            return;
        }

        if ($booking->wasChanged('status')) {
            DB::table('booking_seats')->where('idbooking', $booking->id)->update(['status' => $booking->status]);
        }
    }

    /**
     * The lowest free seat numbers below the slot capacity. When the slot has fewer free
     * seats than needed, taken numbers fill the rest so the seat index rejects the write.
     *
     * @return list<int>
     */
    private static function seatNumbers(int $scheduleId, int $needed): array
    {
        $capacity = max(1, (int) DB::table('schedules')
            ->join('services', 'services.id', '=', 'schedules.idlayanan')
            ->where('schedules.id', $scheduleId)
            ->value('services.kapasitas'));

        $taken = DB::table('booking_seats')
            ->where('active_idschedule', $scheduleId)
            ->pluck('seat_no')
            ->map(fn ($seat): int => (int) $seat)
            ->all();

        $free = array_values(array_diff(range(0, $capacity - 1), $taken));
        $seats = array_slice($free, 0, $needed);
        $filler = 0;
        while (count($seats) < $needed) {
            $seats[] = $taken[$filler % max(1, count($taken))] ?? 0;
            $filler++;
        }

        return $seats;
    }
}
