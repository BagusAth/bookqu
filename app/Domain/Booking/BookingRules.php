<?php

namespace App\Domain\Booking;

use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingRules
{
    /**
     * Validate that multi-slot schedules are contiguous without any gaps: each slot starts
     * exactly where the previous slot ends, read from the stored slot boundaries rather than
     * the service's current duration (BQ-114 decision 4).
     * Assumes schedules are already sorted by jam_mulai ascending.
     *
     * @param  array<int, array{jam_mulai: string, jam_selesai: string}|object>  $orderedSchedules
     */
    public static function validateContiguousSlots(array $orderedSchedules): bool
    {
        return BookingTimeRange::areContiguous($orderedSchedules);
    }

    /**
     * Apply the slot-occupying conditions to a query builder, join clause, or eloquent builder.
     * This is the single home of the occupancy rule (INV-BOOK-03).
     *
     * Occupied: `paid`, `completed`, and `pending` while its payment is `pending` and
     * `now < payments.expired_at + settlement margin` (ADR-0009 decision 2). Legacy pending
     * bookings whose payment has no deadline hold the slot for the booking payment window
     * plus margin from their creation.
     *
     * Not occupied: `cancelled`, and `pending` once its payment failed, succeeded without
     * marking it paid (anomaly), or passed its deadline plus margin.
     */
    public static function applyOccupiesSlotCondition(mixed $query, string $prefix = 'bookings.'): mixed
    {
        $prefix = $prefix !== '' ? rtrim($prefix, '.').'.' : '';
        $holdCutoff = PaymentRules::settlementCutoff();
        $legacyCutoff = self::legacyHoldCutoff();
        $paymentOfBooking = $prefix === '' ? 'bookings.idpayment' : $prefix.'idpayment';

        return $query->where(function ($q) use ($prefix, $holdCutoff, $legacyCutoff, $paymentOfBooking) {
            $q->whereIn($prefix.'status', [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED])
                ->orWhere(function ($pending) use ($prefix, $holdCutoff, $legacyCutoff, $paymentOfBooking) {
                    $pending->where($prefix.'status', BookingState::STATUS_PENDING)
                        ->where(function ($hold) use ($prefix, $holdCutoff, $legacyCutoff, $paymentOfBooking) {
                            $hold->whereExists(function ($payment) use ($holdCutoff, $paymentOfBooking) {
                                $payment->selectRaw('1')
                                    ->from('payments')
                                    ->whereColumn('payments.id', $paymentOfBooking)
                                    ->where('payments.status', PaymentState::STATUS_PENDING)
                                    ->where('payments.expired_at', '>', $holdCutoff);
                            })->orWhere(function ($legacy) use ($prefix, $legacyCutoff, $paymentOfBooking) {
                                $legacy->where($prefix.'created_at', '>=', $legacyCutoff)
                                    ->whereNotExists(function ($payment) use ($paymentOfBooking) {
                                        $payment->selectRaw('1')
                                            ->from('payments')
                                            ->whereColumn('payments.id', $paymentOfBooking)
                                            ->where(function ($decided) {
                                                $decided->whereNotNull('payments.expired_at')
                                                    ->orWhere('payments.status', '!=', PaymentState::STATUS_PENDING);
                                            });
                                    });
                            });
                        });
                });
        });
    }

    public static function onlinePaymentHoldEnd(Booking $booking, Payment $payment): ?Carbon
    {
        if ((int) $payment->idtenant !== (int) $booking->idtenant
            || ! PaymentRules::hasProviderLink($payment) || $payment->status !== PaymentState::STATUS_PENDING) {
            return null;
        }

        $deadline = $payment->expired_at;
        if ($deadline !== null) {
            return Carbon::parse($deadline)->gt(PaymentRules::settlementCutoff())
                ? Carbon::parse($deadline)->addMinutes(PaymentRules::settlementMarginMinutes()) : null;
        }
        if ($booking->created_at === null || $booking->created_at->lt(self::legacyHoldCutoff())) {
            return null;
        }

        return $booking->created_at->copy()->addMinutes(PaymentRules::deadlineMinutes(PaymentState::TIPE_BOOKING) + PaymentRules::settlementMarginMinutes());
    }

    private static function legacyHoldCutoff(): Carbon
    {
        return PaymentRules::settlementCutoff()->subMinutes(PaymentRules::deadlineMinutes(PaymentState::TIPE_BOOKING));
    }

    /**
     * Check if a slot is already occupied by an active booking.
     */
    public static function isSlotOccupied(int $scheduleId, ?int $excludeBookingId = null): bool
    {
        $query = DB::table('bookings')
            ->where('idschedule', $scheduleId)
            ->when($excludeBookingId !== null, fn ($q) => $q->where('id', '!=', $excludeBookingId));

        return self::applyOccupiesSlotCondition($query, '')->exists();
    }

    public static function maximumAdvanceDays(Tenant $tenant): int
    {
        return $tenant->booking_max_days_ahead ?? 30;
    }

    public static function hasSlotPolicy(Tenant $tenant): bool
    {
        return $tenant->booking_min_lead_hours > 0 || $tenant->booking_max_days_ahead !== null || $tenant->booking_buffer_minutes > 0;
    }

    /** @param array<string, list<array{Carbon, Carbon}>> $occupied */
    public static function slotAllowedByPolicy(Tenant $tenant, string $date, string $start, string $end, Carbon $now, array $occupied): bool
    {
        $slotStart = Carbon::parse($date.' '.$start, 'Asia/Jakarta');
        if (($tenant->booking_min_lead_hours > 0 && $slotStart->lt($now->copy()->addHours($tenant->booking_min_lead_hours)))
            || ($tenant->booking_max_days_ahead !== null && $slotStart->toDateString() > $now->copy()->addDays($tenant->booking_max_days_ahead)->toDateString())) {
            return false;
        }

        $buffer = $tenant->booking_buffer_minutes;
        $slotEnd = Carbon::instance(BookingTimeRange::forSlot($date, $start, $end)->end ?? throw new \LogicException('Slot end is required.'));
        $nearby = array_merge(
            $occupied[$slotStart->copy()->subDay()->toDateString()] ?? [],
            $occupied[$date] ?? [],
            $occupied[$slotStart->copy()->addDay()->toDateString()] ?? [],
        );
        foreach ($nearby as [$busyStart, $busyEnd]) {
            if ($slotStart->lt($busyEnd->copy()->addMinutes($buffer))
                && $slotEnd->gt($busyStart->copy()->subMinutes($buffer))) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, list<array{Carbon, Carbon}>> */
    public static function occupiedRanges(Tenant $tenant, int $serviceId, string $firstDate, string $lastDate): array
    {
        // The configured buffer is at most 24 hours, so include adjacent calendar dates.
        $query = DB::table('bookings')->join('schedules', 'schedules.id', '=', 'bookings.idschedule')
            ->where('bookings.idtenant', $tenant->id)->where('bookings.idlayanan', $serviceId)
            ->where('schedules.idtenant', $tenant->id)
            ->whereBetween('bookings.tanggalbooking', [
                Carbon::parse($firstDate)->subDay()->toDateString(),
                Carbon::parse($lastDate)->addDay()->toDateString(),
            ]);

        return self::applyOccupiesSlotCondition($query)->get(['bookings.tanggalbooking', 'schedules.jam_mulai', 'schedules.jam_selesai'])
            ->map(function ($slot): array {
                $date = Carbon::parse($slot->tanggalbooking)->toDateString();

                return [
                    Carbon::parse($date.' '.$slot->jam_mulai, 'Asia/Jakarta'),
                    Carbon::instance(BookingTimeRange::forSlot($date, (string) $slot->jam_mulai, (string) $slot->jam_selesai)->end ?? throw new \LogicException('Slot end is required.')),
                ];
            })->groupBy(fn (array $range) => $range[0]->toDateString())->map(fn ($ranges) => $ranges->values()->all())->all();
    }

    public static function canRequestRefund(Booking $booking): bool
    {
        $tenant = $booking->tenant;

        return $booking->status === BookingState::STATUS_PAID
            && $tenant instanceof Tenant && (bool) $tenant->refunds_enabled
            && self::isBeforeCancellationDeadline($booking);
    }

    private static function isBeforeCancellationDeadline(Booking $booking): bool
    {
        $tenant = $booking->tenant;
        if (! $tenant) {
            return false;
        }

        $cancelBeforeHours = $tenant->cancel_before_hours ?? 24;
        $bookingDateStr = $booking->tanggalbooking instanceof Carbon
            ? $booking->tanggalbooking->toDateString()
            : Carbon::parse($booking->tanggalbooking)->toDateString();
        $bookingDateTime = Carbon::parse($bookingDateStr.' '.$booking->jam);

        return now()->addHours($cancelBeforeHours)->lessThan($bookingDateTime);
    }

    /**
     * Verify whether a booking can be rescheduled by a customer based on business policy.
     */
    public static function canReschedule(Booking $booking): bool
    {
        if ($booking->status !== BookingState::STATUS_PAID) {
            return false;
        }

        if ($booking->isMultiSlot()) {
            return false;
        }

        $tenant = $booking->tenant;
        if (! $tenant) {
            return false;
        }

        $rescheduleBeforeHours = $tenant->reschedule_before_hours ?? 24;
        $bookingDateStr = $booking->tanggalbooking instanceof Carbon
            ? $booking->tanggalbooking->toDateString()
            : Carbon::parse($booking->tanggalbooking)->toDateString();
        $bookingDateTime = Carbon::parse($bookingDateStr.' '.$booking->jam);

        return now()->addHours($rescheduleBeforeHours)->lessThan($bookingDateTime);
    }
}
