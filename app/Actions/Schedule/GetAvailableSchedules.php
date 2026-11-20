<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Actions\Booking\QuoteReschedule;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingTimeRange;
use App\Domain\Schedule\AvailabilityRules;
use App\Domain\Schedule\PriceRules;
use App\Domain\Schedule\SlotPricing;
use App\Domain\Schedule\SlotRefusal;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetAvailableSchedules
{
    public function __construct(
        protected ScheduleAvailabilityCache $availabilityCache
    ) {}

    /**
     * Get 30-day availability payload for date selection.
     *
     * @return array<int, array{date: string, total_slots: int, available_slots: int, is_blocked: bool}>
     */
    public function getDateAvailability(
        Tenant $tenant,
        Service $service,
        ?Carbon $minDate = null,
        ?Carbon $maxDate = null
    ): array {
        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);
        $minDate = $minDate ? $minDate->copy()->setTimezone($wib) : Carbon::today($wib);
        $maxDate = $maxDate ? $maxDate->copy()->setTimezone($wib) : Carbon::today($wib)->addDays(BookingRules::maximumAdvanceDays($tenant));

        $todayStr = $minDate->toDateString();
        $nowTimeStr = $nowWib->format('H:i');

        $cacheKey = $this->availabilityCache->getAvailabilityCacheKey((int) $tenant->id, (int) $service->id);

        $loadDates = function () use ($tenant, $service, $minDate, $maxDate, $todayStr, $nowTimeStr): array {
            $rows = $this->countPerDate(
                DB::table('schedules')
                    ->where('schedules.idtenant', $tenant->id)
                    ->where('schedules.idlayanan', $service->id)
                    ->where('schedules.status', 'tersedia')
                    ->whereBetween('schedules.tanggal', [$minDate->toDateString(), $maxDate->toDateString()]),
                BookingRules::hasRoomSql(),
                $todayStr,
                $nowTimeStr,
            );

            $blockedDates = DB::table('owner_blocked_dates')
                ->where('idtenant', $tenant->id)
                ->whereBetween('tanggal', [$minDate->toDateString(), $maxDate->toDateString()])
                ->pluck('tanggal')
                ->map(fn ($d) => Carbon::parse($d)->toDateString())
                ->all();

            return $rows->map(function ($row) use ($blockedDates) {
                $dateStr = Carbon::parse($row->tanggal)->toDateString();
                $isBlocked = in_array($dateStr, $blockedDates, true);

                return [
                    'date' => $dateStr,
                    'total_slots' => (int) $row->total_slots,
                    'available_slots' => $isBlocked ? 0 : (int) $row->available_slots,
                    'is_blocked' => $isBlocked,
                ];
            })->values()->all();
        };

        $usesUnits = BookingRules::activeUnitIds((int) $tenant->id, (int) $service->id) !== [];
        if (! BookingRules::hasSlotPolicy($tenant) && ! $usesUnits) {
            return Cache::remember($cacheKey, now()->addSeconds(60), $loadDates);
        }

        // Booking policy and shared units need each slot evaluated; the counts come from the same
        // rules the time selection uses.
        $dates = $loadDates();
        $counts = [];
        $hasPolicy = BookingRules::hasSlotPolicy($tenant);
        $occupied = $hasPolicy ? BookingRules::occupiedRanges($tenant, $service->id, $minDate->toDateString(), $maxDate->toDateString()) : [];
        $unitBusy = BookingRules::unitBusyRanges((int) $tenant->id, (int) $service->id, $minDate->toDateString(), $maxDate->toDateString());
        [$hasRoom, $roomBindings] = BookingRules::hasRoomSql();
        $slots = DB::table('schedules')->where('idtenant', $tenant->id)->where('idlayanan', $service->id)
            ->where('status', 'tersedia')->whereBetween('tanggal', [$minDate->toDateString(), $maxDate->toDateString()])
            ->whereRaw($hasRoom, $roomBindings)
            ->get(['id', 'tanggal', 'jam_mulai', 'jam_selesai']);
        foreach ($slots as $slot) {
            $date = Carbon::parse($slot->tanggal)->toDateString();
            if (Carbon::parse($date.' '.$slot->jam_mulai, $wib)->gt($nowWib)
                && (! $hasPolicy || BookingRules::slotAllowedByPolicy($tenant, $date, $slot->jam_mulai, $slot->jam_selesai, $nowWib, $occupied))
                && ! BookingRules::slotHitsBusyUnit($unitBusy, (int) $slot->id, $date, (string) $slot->jam_mulai, (string) $slot->jam_selesai)) {
                $counts[$date] = ($counts[$date] ?? 0) + 1;
            }
        }

        return array_map(static function (array $day) use ($counts): array {
            $day['available_slots'] = $day['is_blocked'] ? 0 : ($counts[$day['date']] ?? 0);

            return $day;
        }, $dates);
    }

    /**
     * Count available slots on a specific date for a service.
     *
     * @param  string  $date  (Y-m-d)
     */
    public function countAvailableSlotsOnDate(Tenant $tenant, Service $service, string $date): int
    {
        if (BookingRules::hasSlotPolicy($tenant) || BookingRules::activeUnitIds((int) $tenant->id, (int) $service->id) !== []) {
            return count(array_filter($this->getTimeSlots($tenant, $service, $date), fn (array $slot) => $slot['is_available']));
        }

        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);
        $isToday = Carbon::parse($date, $wib)->isSameDay($nowWib);

        [$hasRoom, $roomBindings] = BookingRules::hasRoomSql();
        $query = DB::table('schedules')
            ->where('schedules.idtenant', $tenant->id)
            ->where('schedules.idlayanan', $service->id)
            ->where('schedules.status', 'tersedia')
            ->whereDate('schedules.tanggal', $date)
            ->whereRaw($hasRoom, $roomBindings);

        if ($isToday) {
            $nowTimeStr = $nowWib->format('H:i');
            $query->whereRaw('substr(schedules.jam_mulai, 1, 5) > ?', [$nowTimeStr]);
        }

        return $query->count();
    }

    /**
     * Get time slots payload for customer time selection on a specific date.
     *
     * @param  string  $date  (Y-m-d)
     */
    public function getTimeSlots(Tenant $tenant, Service $service, string $date): array
    {
        $dateStr = Carbon::parse($date)->toDateString();
        $cacheKey = $this->availabilityCache->getSchedulesCacheKey((int) $tenant->id, (int) $service->id, $dateStr);

        $scheduleRows = Cache::remember($cacheKey, now()->addSeconds(300), function () use ($tenant, $service, $dateStr) {
            [$seatsTaken, $seatBindings] = BookingRules::seatsTakenSql();

            return DB::table('schedules')
                ->join('services', 'services.id', '=', 'schedules.idlayanan')
                ->where('schedules.idtenant', $tenant->id)
                ->where('schedules.idlayanan', $service->id)
                ->where('schedules.status', 'tersedia')
                ->whereDate('schedules.tanggal', $dateStr)
                ->orderBy('schedules.jam_mulai')
                ->select([
                    'schedules.id',
                    'schedules.jam_mulai',
                    'schedules.jam_selesai',
                    'schedules.tanggal',
                    'schedules.harga_override',
                    'services.kapasitas',
                ])
                ->selectRaw("{$seatsTaken} as seats_taken", $seatBindings)
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => (int) $row->id,
                        'jam_mulai' => $row->jam_mulai,
                        'jam_selesai' => $row->jam_selesai,
                        'tanggal' => $row->tanggal,
                        'harga_override' => $row->harga_override !== null ? (float) $row->harga_override : null,
                        'seats_left' => max(0, BookingRules::capacityFrom($row->kapasitas) - (int) $row->seats_taken),
                    ];
                })
                ->all();
        });

        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);
        $selectedDateCarbon = Carbon::parse($dateStr, $wib);
        $isToday = $selectedDateCarbon->isSameDay($nowWib);
        $isPastDate = $selectedDateCarbon->lt($nowWib->copy()->startOfDay());

        $occupied = $tenant->booking_buffer_minutes > 0
            ? BookingRules::occupiedRanges($tenant, $service->id, $dateStr, $dateStr) : [];

        $priceRules = PriceRules::forTenant($tenant, $dateStr, $dateStr);
        // Outside the cache: another service's booking on a shared unit does not clear this cache.
        $unitBusy = BookingRules::unitBusyRanges((int) $tenant->id, (int) $service->id, $dateStr, $dateStr);

        return collect($scheduleRows)->map(function (array $row) use ($isToday, $isPastDate, $nowWib, $dateStr, $wib, $service, $tenant, $occupied, $priceRules, $unitBusy) {
            $slotDateTime = Carbon::parse($dateStr.' '.$row['jam_mulai'], $wib);
            $hour = (int) $slotDateTime->format('H');
            $session = 'evening';

            if ($hour >= 5 && $hour <= 11) {
                $session = 'morning';
            } elseif ($hour >= 12 && $hour <= 17) {
                $session = 'afternoon';
            }

            $isPast = $isPastDate || ($isToday && $slotDateTime->lessThanOrEqualTo($nowWib));
            $isBooked = $row['seats_left'] < 1
                || BookingRules::slotHitsBusyUnit($unitBusy, $row['id'], $dateStr, (string) $row['jam_mulai'], (string) $row['jam_selesai']);
            $isAvailable = ! $isPast && ! $isBooked
                && (! BookingRules::hasSlotPolicy($tenant)
                    || BookingRules::slotAllowedByPolicy($tenant, $dateStr, $row['jam_mulai'], $row['jam_selesai'], $nowWib, $occupied));
            $slotPrice = SlotPricing::price($service, $dateStr, (string) $row['jam_mulai'], $row['harga_override'], $priceRules)['price'];
            // BQ-114: the slot's own end, not the service's current duration
            $slotRange = BookingTimeRange::forSlot($dateStr, (string) $row['jam_mulai'], (string) $row['jam_selesai']);
            $timeRangeLabel = $slotRange->label();

            return [
                'id' => $row['id'],
                'time' => $slotDateTime->format('H:i'),
                'label' => $timeRangeLabel,
                'start_time' => $slotDateTime->format('H:i'),
                'end_time' => $slotRange->endTime(),
                'duration_minutes' => $slotRange->durationMinutes(),
                'range_label' => $timeRangeLabel,
                'time_range' => $timeRangeLabel,
                'period' => 'WIB',
                'session' => $session,
                'price' => $slotPrice,
                'price_label' => 'Rp '.number_format($slotPrice, 0, ',', '.'),
                'is_available' => $isAvailable,
                'is_disabled' => ! $isAvailable,
                'is_booked' => $isBooked,
                'is_past' => $isPast,
                'seats_left' => $isBooked ? 0 : $row['seats_left'],
            ];
        })->values()->all();
    }

    /**
     * Get available slots for reschedule / owner booking.
     *
     * @param  string  $date  (Y-m-d)
     */
    public function getSlotsForReschedule(
        Tenant $tenant,
        int $serviceId,
        string $date,
        ?int $excludeBookingId = null,
        int $participants = 1,
    ): Collection {
        [$seatsTaken, $bindings] = BookingRules::seatsTakenSql(excludeBookingId: $excludeBookingId);
        // scope-bypass: filtered by the resolved storefront tenant
        $slots = Schedule::withoutGlobalScopes()->with('layanan')
            ->select('schedules.*')->selectRaw($seatsTaken.' AS seats_taken', $bindings)
            ->withExists(['bookings as current_booking' => fn ($query) => $query->where('id', $excludeBookingId ?? 0)])
            ->where('idtenant', $tenant->id)
            ->where('idlayanan', $serviceId)
            ->whereDate('tanggal', $date)
            ->where('status', 'tersedia')
            ->orderBy('jam_mulai')
            ->get();

        $unitBusy = BookingRules::unitBusyRanges((int) $tenant->id, $serviceId, $date, $date, $excludeBookingId);
        $dateBlocked = AvailabilityRules::isDateBlocked((int) $tenant->id, $date);

        return $slots->map(function ($slot) use ($excludeBookingId, $participants, $unitBusy, $dateBlocked) {
            $service = $slot->layanan;
            $isAvailable = AvailabilityRules::refusal($slot, excludeBookingId: $excludeBookingId, participants: $participants,
                seatsTaken: (int) $slot->getAttribute('seats_taken'), unitBusy: $unitBusy, dateBlocked: $dateBlocked) === null;
            $isCurrentSlot = (bool) $slot->getAttribute('current_booking');

            return [
                'id' => $slot->id,
                'schedule' => $slot,
                'jam_mulai' => substr($slot->jam_mulai, 0, 5),
                'jam_selesai' => substr($slot->jam_selesai, 0, 5),
                'is_available' => $isAvailable,
                'is_current' => $isCurrentSlot,
                'seats_left' => max(0, BookingRules::capacityFrom($service instanceof Service ? $service->kapasitas : null) - (int) $slot->getAttribute('seats_taken')),
            ];
        });
    }

    /** @return array{slots: list<array{id: int, jam_mulai: string, jam_selesai: string, is_available: bool, is_booked: bool, is_past: bool}>, message: ?string} */
    public function getCustomerRescheduleSlots(Booking $booking, Tenant $tenant, Service $service, string $date): array
    {
        $days = $this->customerRescheduleDays($booking, $tenant, $service, $date, $date);
        $day = $days[$date] ?? null;

        return [
            'slots' => $day['slots'] ?? [],
            'message' => ($day['hasHigherPrice'] ?? false) ? 'Ada sesi dengan harga lebih tinggi. Hubungi pemilik.' : null,
        ];
    }

    /** @return array{availabilityPayload: list<array{date: string, total_slots: int, available_slots: int}>, minDate: string, maxDate: string} */
    public function getCustomerRescheduleCalendar(Booking $booking): array
    {
        $today = Carbon::today('Asia/Jakarta');
        $minDate = $today->toDateString();
        $maxDate = $today->addDays(30)->toDateString();
        $tenant = $booking->tenant;
        $service = $booking->layanan;
        $days = $tenant instanceof Tenant && $service instanceof Service
            ? $this->customerRescheduleDays($booking, $tenant, $service, $minDate, $maxDate)
            : [];

        $availabilityPayload = [];
        foreach ($days as $date => $day) {
            $availabilityPayload[] = [
                'date' => $date,
                'total_slots' => $day['total'],
                'available_slots' => count(array_filter($day['slots'], fn (array $slot): bool => $slot['is_available'])),
            ];
        }

        return compact('availabilityPayload', 'minDate', 'maxDate');
    }

    /** @return array<string, array{slots: list<array{id: int, jam_mulai: string, jam_selesai: string, is_available: bool, is_booked: bool, is_past: bool}>, total: int, hasHigherPrice: bool}> */
    private function customerRescheduleDays(Booking $booking, Tenant $tenant, Service $service, string $from, string $through): array
    {
        $bookingId = (int) $booking->id;
        $tenantId = (int) $tenant->id;
        $serviceId = (int) $service->id;
        $participants = max(1, (int) $booking->peserta);
        [$seatsTaken, $bindings] = BookingRules::seatsTakenSql(excludeBookingId: $bookingId);

        // scope-bypass: limited to the tenant and service of the authorized booking.
        $schedules = Schedule::withoutGlobalScopes()
            ->where('schedules.idtenant', $tenantId)
            ->where('schedules.idlayanan', $serviceId)
            ->where('schedules.status', 'tersedia')
            ->whereDate('schedules.tanggal', '>=', $from)
            ->whereDate('schedules.tanggal', '<=', $through)
            ->where('schedules.id', '!=', (int) $booking->idschedule)
            ->whereNotExists(function ($closed) use ($tenantId) {
                $closed->selectRaw('1')->from('owner_blocked_dates')
                    ->where('owner_blocked_dates.idtenant', $tenantId)
                    ->whereRaw('substr(owner_blocked_dates.tanggal, 1, 10) = substr(schedules.tanggal, 1, 10)');
            })
            ->orderBy('schedules.tanggal')->orderBy('schedules.jam_mulai')
            ->select('schedules.*')->selectRaw($seatsTaken.' AS seats_taken', $bindings)
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $prices = app(QuoteReschedule::class)->execute($booking, $tenant, $service, $from, $schedules, $through);
        $unitBusy = BookingRules::unitBusyRanges($tenantId, $serviceId, $from, $through, $bookingId);
        $now = Carbon::now('Asia/Jakarta');
        $days = [];

        foreach ($schedules as $schedule) {
            $date = substr((string) $schedule->tanggal, 0, 10);
            $days[$date] ??= ['slots' => [], 'total' => 0, 'hasHigherPrice' => false];
            $days[$date]['total']++;

            $schedule->setRelation('layanan', $service);
            $refusal = AvailabilityRules::refusal($schedule, $now, $bookingId, participants: $participants,
                seatsTaken: (int) $schedule->getAttribute('seats_taken'), unitBusy: $unitBusy, dateBlocked: false);
            if (($prices[$schedule->id]['price_difference'] ?? 0) > 0) {
                $days[$date]['hasHigherPrice'] = true;

                continue;
            }

            $range = BookingTimeRange::forSlot($date, (string) $schedule->jam_mulai, (string) $schedule->jam_selesai);
            $days[$date]['slots'][] = [
                'id' => (int) $schedule->id,
                'jam_mulai' => $range->startTime(),
                'jam_selesai' => $range->endTime() ?? '',
                'is_available' => $refusal === null,
                'is_booked' => $refusal !== null && $refusal !== SlotRefusal::Past,
                'is_past' => $refusal === SlotRefusal::Past,
            ];
        }

        return $days;
    }

    /**
     * Total and bookable slots per date. Room is evaluated per slot in a derived table, then
     * grouped: MariaDB refuses the correlated seat sum inside an aggregate.
     *
     * @param  array{0: string, 1: list<mixed>}  $hasRoom  from BookingRules::hasRoomSql()
     * @return Collection<int, \stdClass> rows of `tanggal`, `total_slots`, `available_slots`
     */
    private function countPerDate(Builder $slots, array $hasRoom, string $todayStr, string $nowTimeStr): Collection
    {
        [$roomSql, $roomBindings] = $hasRoom;
        $slots->select(['schedules.tanggal', 'schedules.jam_mulai'])
            ->selectRaw("case when {$roomSql} then 1 else 0 end as has_room", $roomBindings);

        return DB::query()->fromSub($slots, 'slots')
            ->groupBy('slots.tanggal')
            ->orderBy('slots.tanggal')
            ->select(['slots.tanggal', DB::raw('count(*) as total_slots')])
            ->selectRaw('sum(case when slots.has_room = 1 and (substr(slots.tanggal, 1, 10) > ? or (substr(slots.tanggal, 1, 10) = ? and substr(slots.jam_mulai, 1, 5) > ?)) then 1 else 0 end) as available_slots', [$todayStr, $todayStr, $nowTimeStr])
            ->get();
    }
}
