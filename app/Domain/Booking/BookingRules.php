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
     * Occupied: `paid`, `completed`, `no_show` (ADR-0018 decision 4), and `pending` while its payment is `pending` and
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
            $q->whereIn($prefix.'status', [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED, BookingState::STATUS_NO_SHOW])
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

    /**
     * Participants holding seats of a slot under the occupancy rule (FR-BOOKING-021).
     */
    public static function seatsTaken(int $scheduleId, ?int $excludeBookingId = null): int
    {
        $query = DB::table('bookings')
            ->where('idschedule', $scheduleId)
            ->when($excludeBookingId !== null, fn ($q) => $q->where('id', '!=', $excludeBookingId));

        return (int) self::applyOccupiesSlotCondition($query, '')->sum('peserta');
    }

    /** Capacity per slot of the schedule's service (ADR-0017 decision 1). */
    public static function capacityOf(int $scheduleId): int
    {
        return max(1, (int) DB::table('schedules')
            ->join('services', 'services.id', '=', 'schedules.idlayanan')
            ->where('schedules.id', $scheduleId)
            ->value('services.kapasitas'));
    }

    /**
     * Whether a slot still has room for the participants; "slot full" is its negation.
     */
    public static function hasRoomFor(int $scheduleId, int $participants = 1, ?int $excludeBookingId = null): bool
    {
        return self::seatsTaken($scheduleId, $excludeBookingId) + max(1, $participants) <= self::capacityOf($scheduleId);
    }

    /**
     * SQL fragment "participants holding seats" for the schedule row aliased `$schedules`,
     * for listings that evaluate many slots in one query (ADR-0005 decision 8).
     *
     * @return array{0: string, 1: list<mixed>}
     */
    public static function seatsTakenSql(string $schedules = 'schedules', ?int $excludeBookingId = null): array
    {
        $query = DB::table('bookings as seat_bookings')
            ->selectRaw('coalesce(sum(seat_bookings.peserta), 0)')
            ->whereColumn('seat_bookings.idschedule', $schedules.'.id')
            ->when($excludeBookingId !== null, fn ($q) => $q->where('seat_bookings.id', '!=', $excludeBookingId));
        self::applyOccupiesSlotCondition($query, 'seat_bookings.');

        return ['('.$query->toSql().')', $query->getBindings()];
    }

    /**
     * SQL condition "the slot has room for `$participants`" for the schedule row aliased `$schedules`.
     *
     * @return array{0: string, 1: list<mixed>}
     */
    public static function hasRoomSql(string $schedules = 'schedules', int $participants = 1, ?int $excludeBookingId = null): array
    {
        [$taken, $bindings] = self::seatsTakenSql($schedules, $excludeBookingId);

        return [
            "{$taken} + ? <= (select room_services.kapasitas from services as room_services where room_services.id = {$schedules}.idlayanan)",
            [...$bindings, max(1, $participants)],
        ];
    }

    /** Active unit ids a service uses (FR-UNIT-002). @return list<int> */
    public static function activeUnitIds(int $tenantId, int $serviceId): array
    {
        return DB::table('service_unit')
            ->join('units', 'units.id', '=', 'service_unit.idunit')
            ->where('service_unit.idservice', $serviceId)
            ->where('units.idtenant', $tenantId)
            ->where('units.is_active', true)
            ->pluck('units.id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * Time ranges held on the units of a service by occupying bookings of any service that
     * uses one of those units, keyed by date, with the schedule each range belongs to.
     *
     * @return array<string, list<array{0: Carbon, 1: Carbon, 2: int}>>
     */
    public static function unitBusyRanges(int $tenantId, int $serviceId, string $firstDate, string $lastDate, ?int $excludeBookingId = null): array
    {
        $unitIds = self::activeUnitIds($tenantId, $serviceId);
        if ($unitIds === []) {
            return [];
        }

        $query = DB::table('bookings')->join('schedules', 'schedules.id', '=', 'bookings.idschedule')
            ->where('bookings.idtenant', $tenantId)
            ->whereIn('bookings.idlayanan', DB::table('service_unit')->whereIn('idunit', $unitIds)->select('idservice'))
            ->whereBetween('schedules.tanggal', [Carbon::parse($firstDate)->subDay()->toDateString(), Carbon::parse($lastDate)->addDay()->toDateString()])
            ->when($excludeBookingId !== null, fn ($q) => $q->where('bookings.id', '!=', $excludeBookingId));

        return self::applyOccupiesSlotCondition($query)
            ->get(['schedules.id', 'schedules.tanggal', 'schedules.jam_mulai', 'schedules.jam_selesai'])
            ->unique('id')
            ->map(function ($slot): array {
                $date = Carbon::parse($slot->tanggal)->toDateString();
                $range = BookingTimeRange::forSlot($date, (string) $slot->jam_mulai, (string) $slot->jam_selesai);

                return [Carbon::instance($range->start), Carbon::instance($range->end ?? $range->start), (int) $slot->id];
            })->groupBy(fn (array $range) => $range[0]->toDateString())->map(fn ($ranges) => $ranges->values()->all())->all();
    }

    /**
     * The one unit-conflict rule (ADR-0017 decision 4, FR-UNIT-003): a slot conflicts when a
     * range in `$busy` from another schedule overlaps it in time. Seats on the same schedule
     * are capacity, not a unit conflict.
     *
     * @param  array<string, list<array{0: Carbon, 1: Carbon, 2: int}>>  $busy
     */
    public static function slotHitsBusyUnit(array $busy, int $scheduleId, string $date, string $start, string $end): bool
    {
        if ($busy === []) {
            return false;
        }

        $range = BookingTimeRange::forSlot($date, $start, $end);
        $slotStart = Carbon::instance($range->start);
        $slotEnd = Carbon::instance($range->end ?? $range->start);
        $nearby = array_merge(
            $busy[$slotStart->copy()->subDay()->toDateString()] ?? [],
            $busy[$slotStart->toDateString()] ?? [],
            $busy[$slotStart->copy()->addDay()->toDateString()] ?? [],
        );
        foreach ($nearby as [$busyStart, $busyEnd, $busySchedule]) {
            if ($busySchedule !== $scheduleId && $slotStart->lt($busyEnd) && $busyStart->lt($slotEnd)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pairs of occupying bookings of the given services that overlap in time on different
     * schedules: the conflicts a unit used by these services has (FR-UNIT-003/004).
     *
     * @param  list<int>  $serviceIds
     * @return list<array{0: int, 1: int}> booking id pairs, lower id first
     */
    public static function unitOverlaps(int $tenantId, array $serviceIds, ?string $fromDate = null): array
    {
        if (count($serviceIds) === 0) {
            return [];
        }

        $query = DB::table('bookings')->join('schedules', 'schedules.id', '=', 'bookings.idschedule')
            ->where('bookings.idtenant', $tenantId)
            ->whereIn('bookings.idlayanan', $serviceIds)
            ->when($fromDate !== null, fn ($q) => $q->where('schedules.tanggal', '>=', $fromDate))
            ->orderBy('schedules.tanggal')->orderBy('schedules.jam_mulai');

        $ranges = self::applyOccupiesSlotCondition($query)
            ->get(['bookings.id', 'schedules.id as schedule_id', 'schedules.tanggal', 'schedules.jam_mulai', 'schedules.jam_selesai'])
            ->map(function ($row): array {
                $range = BookingTimeRange::forSlot(Carbon::parse($row->tanggal)->toDateString(), (string) $row->jam_mulai, (string) $row->jam_selesai);

                return ['id' => (int) $row->id, 'schedule' => (int) $row->schedule_id, 'start' => $range->start, 'end' => $range->end ?? $range->start];
            })->sortBy('start')->values()->all();

        $pairs = [];
        foreach ($ranges as $i => $a) {
            for ($j = $i + 1; $j < count($ranges) && $ranges[$j]['start'] < $a['end']; $j++) {
                $b = $ranges[$j];
                if ($b['schedule'] !== $a['schedule'] && $a['start'] < $b['end']) {
                    $pairs[] = [min($a['id'], $b['id']), max($a['id'], $b['id'])];
                }
            }
        }

        return $pairs;
    }

    /** Whether a schedule's slot is blocked by a unit already in use at that time. */
    public static function unitConflict(object $schedule, ?int $excludeBookingId = null): bool
    {
        $date = Carbon::parse($schedule->tanggal)->toDateString();
        $busy = self::unitBusyRanges((int) $schedule->idtenant, (int) $schedule->idlayanan, $date, $date, $excludeBookingId);

        return self::slotHitsBusyUnit($busy, (int) $schedule->id, $date, (string) $schedule->jam_mulai, (string) $schedule->jam_selesai);
    }

    /**
     * Whether the session of a booking group has ended, the time condition of `no_show`
     * (ADR-0018 decision 3). A group without a known end has not ended.
     *
     * @param  list<Booking>  $group  bookings of one payment group, with their schedules
     */
    public static function sessionHasEnded(array $group, ?Carbon $now = null): bool
    {
        $first = $group[0] ?? null;
        if (! $first instanceof Booking) {
            return false;
        }

        $date = Carbon::parse($first->tanggalbooking)->toDateString();
        $end = BookingTimeRange::forSlots($date, array_map(fn (Booking $booking) => $booking->schedule, $group))->end;

        return $end !== null && Carbon::instance($end)->lte($now ?? Carbon::now('Asia/Jakarta'));
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
