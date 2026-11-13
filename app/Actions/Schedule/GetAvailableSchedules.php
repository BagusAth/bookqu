<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingTimeRange;
use App\Domain\Schedule\AvailabilityRules;
use App\Domain\Schedule\PriceRules;
use App\Domain\Schedule\SlotPricing;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use Carbon\Carbon;
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
            [$hasRoom, $roomBindings] = BookingRules::hasRoomSql();
            $rows = DB::table('schedules')
                ->where('schedules.idtenant', $tenant->id)
                ->where('schedules.idlayanan', $service->id)
                ->where('schedules.status', 'tersedia')
                ->whereBetween('schedules.tanggal', [$minDate->toDateString(), $maxDate->toDateString()])
                ->groupBy('schedules.tanggal')
                ->orderBy('schedules.tanggal')
                ->select(['schedules.tanggal', DB::raw('count(distinct schedules.id) as total_slots')])
                ->selectRaw("count(distinct case when {$hasRoom} and (substr(schedules.tanggal, 1, 10) > ? or (substr(schedules.tanggal, 1, 10) = ? and substr(schedules.jam_mulai, 1, 5) > ?)) then schedules.id end) as available_slots", [...$roomBindings, $todayStr, $todayStr, $nowTimeStr])
                ->get();

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
                        'seats_left' => max(0, max(1, (int) $row->kapasitas) - (int) $row->seats_taken),
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
        ?int $excludeBookingId = null
    ): Collection {
        // scope-bypass: filtered by the resolved storefront tenant
        $slots = Schedule::withoutGlobalScopes()->with('layanan')
            ->where('idtenant', $tenant->id)
            ->where('idlayanan', $serviceId)
            ->whereDate('tanggal', $date)
            ->where('status', 'tersedia')
            ->orderBy('jam_mulai')
            ->get();

        return $slots->map(function ($slot) use ($excludeBookingId) {
            $isAvailable = AvailabilityRules::isSlotAvailable($slot, excludeBookingId: $excludeBookingId);
            $isCurrentSlot = ($excludeBookingId !== null && $slot->bookings()->where('id', $excludeBookingId)->exists());

            return [
                'id' => $slot->id,
                'schedule' => $slot,
                'jam_mulai' => substr($slot->jam_mulai, 0, 5),
                'jam_selesai' => substr($slot->jam_selesai, 0, 5),
                'is_available' => $isAvailable,
                'is_current' => $isCurrentSlot,
            ];
        });
    }

    /**
     * Get available time slots for customer reschedule flow (AJAX).
     *
     * @param  string  $selectedDate  (Y-m-d)
     * @return array<int, array{id: int, jam_mulai: string, jam_selesai: string, is_available: bool, is_booked: bool, is_past: bool}>
     */
    public function getCustomerRescheduleSlots(
        Tenant $tenant,
        Service $service,
        string $selectedDate,
        int $excludeBookingId
    ): array {
        $cacheKey = "tenant:{$tenant->id}:service:{$service->id}:schedules:{$selectedDate}:reschedule:{$excludeBookingId}";

        // scope-bypass: the booking being rescheduled, already resolved for this tenant
        $participants = max(1, (int) Booking::withoutGlobalScopes()->whereKey($excludeBookingId)->value('peserta'));
        $unitBusy = BookingRules::unitBusyRanges((int) $tenant->id, (int) $service->id, $selectedDate, $selectedDate, $excludeBookingId);

        $rows = Cache::remember($cacheKey, now()->addSeconds(300), function () use ($tenant, $service, $selectedDate, $excludeBookingId, $participants) {
            [$hasRoom, $roomBindings] = BookingRules::hasRoomSql('schedules', $participants, $excludeBookingId);

            return DB::table('schedules')
                ->where('schedules.idtenant', $tenant->id)
                ->where('schedules.idlayanan', $service->id)
                ->where('schedules.status', 'tersedia')
                ->whereDate('schedules.tanggal', $selectedDate)
                ->orderBy('schedules.jam_mulai')
                ->select(['schedules.id', 'schedules.jam_mulai', 'schedules.jam_selesai'])
                ->selectRaw("case when {$hasRoom} then 0 else 1 end as booking_count", $roomBindings)
                ->get()
                ->map(function ($row) use ($selectedDate) {
                    $wib = 'Asia/Jakarta';
                    $nowWib = Carbon::now($wib);
                    $selectedDateCarbon = Carbon::parse($selectedDate, $wib);
                    $isToday = $selectedDateCarbon->isSameDay($nowWib);
                    $isPastDate = $selectedDateCarbon->lt($nowWib->copy()->startOfDay());
                    $slotDateTime = Carbon::parse($selectedDate.' '.$row->jam_mulai, $wib);
                    $isBooked = (int) $row->booking_count > 0;
                    $isPast = $isPastDate || ($isToday && $slotDateTime->lessThanOrEqualTo($nowWib));
                    $isAvailable = ! $isBooked && ! $isPast;

                    return [
                        'id' => (int) $row->id,
                        'jam_mulai' => $slotDateTime->format('H:i'),
                        'jam_selesai' => Carbon::parse($selectedDate.' '.$row->jam_selesai, $wib)->format('H:i'),
                        'is_available' => $isAvailable,
                        'is_booked' => $isBooked,
                        'is_past' => $isPast,
                        'jam_selesai_raw' => (string) $row->jam_selesai,
                    ];
                })
                ->all();
        });

        return array_map(function (array $slot) use ($unitBusy, $selectedDate): array {
            if ($slot['is_available'] && BookingRules::slotHitsBusyUnit($unitBusy, $slot['id'], $selectedDate, $slot['jam_mulai'], $slot['jam_selesai_raw'])) {
                $slot['is_available'] = false;
                $slot['is_booked'] = true;
            }
            unset($slot['jam_selesai_raw']);

            return $slot;
        }, $rows);
    }

    /** @return array{availabilityPayload: list<array{date: string, total_slots: int, available_slots: int}>, minDate: string, maxDate: string} */
    public function getCustomerRescheduleCalendar(Booking $booking): array
    {
        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);
        $minDate = Carbon::today($wib);
        $maxDate = Carbon::today($wib)->addDays(30);
        $tenantId = $booking->idtenant;
        $serviceId = $booking->idlayanan;

        $todayStr = $minDate->toDateString();
        $nowTimeStr = $nowWib->format('H:i');

        $availabilityKey = sprintf(
            'tenant:%s:service:%s:availability:%s:%s:reschedule:%s',
            $tenantId,
            $serviceId,
            $minDate->toDateString(),
            $maxDate->toDateString(),
            $booking->id
        );

        $availabilityPayload = Cache::remember($availabilityKey, now()->addSeconds(60), function () use ($tenantId, $serviceId, $minDate, $maxDate, $booking, $todayStr, $nowTimeStr) {
            // The booking being moved frees its own seats (excluded).
            [$hasRoom, $roomBindings] = BookingRules::hasRoomSql('schedules', max(1, (int) $booking->peserta), (int) $booking->id);
            $rows = DB::table('schedules')
                ->where('schedules.idtenant', $tenantId)
                ->where('schedules.idlayanan', $serviceId)
                ->where('schedules.status', 'tersedia')
                // Dates closed by the owner are not offered (BUG-T5-08); the claim refuses them too
                ->whereNotExists(function ($closed) use ($booking) {
                    $closed->selectRaw('1')
                        ->from('owner_blocked_dates')
                        ->where('owner_blocked_dates.idtenant', $booking->idtenant)
                        ->whereRaw('substr(owner_blocked_dates.tanggal, 1, 10) = substr(schedules.tanggal, 1, 10)');
                })
                ->whereBetween('schedules.tanggal', [$minDate->toDateString(), $maxDate->toDateString()])
                ->groupBy('schedules.tanggal')
                ->orderBy('schedules.tanggal')
                ->select([
                    'schedules.tanggal',
                    DB::raw('count(distinct schedules.id) as total_slots'),
                ])
                ->selectRaw("count(distinct case when {$hasRoom} and (substr(schedules.tanggal, 1, 10) > ? or (substr(schedules.tanggal, 1, 10) = ? and substr(schedules.jam_mulai, 1, 5) > ?)) then schedules.id end) as available_slots", [...$roomBindings, $todayStr, $todayStr, $nowTimeStr])
                ->get();

            return $rows->map(function ($row) {
                return [
                    'date' => Carbon::parse($row->tanggal)->toDateString(),
                    'total_slots' => (int) $row->total_slots,
                    'available_slots' => (int) $row->available_slots,
                ];
            })->values()->all();
        });

        return [
            'availabilityPayload' => $availabilityPayload,
            'minDate' => $minDate->toDateString(),
            'maxDate' => $maxDate->toDateString(),
        ];
    }
}
