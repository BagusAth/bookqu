<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Schedule\AvailabilityRules;
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
     * @param Tenant $tenant
     * @param Service $service
     * @param Carbon|null $minDate
     * @param Carbon|null $maxDate
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
        $maxDate = $maxDate ? $maxDate->copy()->setTimezone($wib) : Carbon::today($wib)->addDays(30);

        $todayStr = $minDate->toDateString();
        $nowTimeStr = $nowWib->format('H:i');

        $cacheKey = $this->availabilityCache->getAvailabilityCacheKey((int) $tenant->id, (int) $service->id);

        return Cache::remember($cacheKey, now()->addSeconds(60), function () use ($tenant, $service, $minDate, $maxDate, $todayStr, $nowTimeStr) {
            $rows = DB::table('schedules')
                ->leftJoin('bookings', function ($join) {
                    $join->on('schedules.id', '=', 'bookings.idschedule')
                        ->where(function ($q) {
                            $q->whereIn('bookings.status', ['paid', 'completed'])
                              ->orWhere(function ($sub) {
                                  $sub->where('bookings.status', 'pending')
                                      ->where('bookings.created_at', '>=', now()->subMinutes(15));
                              });
                        });
                })
                ->where('schedules.idtenant', $tenant->id)
                ->where('schedules.idlayanan', $service->id)
                ->where('schedules.status', 'tersedia')
                ->whereBetween('schedules.tanggal', [$minDate->toDateString(), $maxDate->toDateString()])
                ->groupBy('schedules.tanggal')
                ->orderBy('schedules.tanggal')
                ->select([
                    'schedules.tanggal',
                    DB::raw('count(distinct schedules.id) as total_slots'),
                    DB::raw("count(distinct case when bookings.id is null and (substr(schedules.tanggal, 1, 10) > '{$todayStr}' or (substr(schedules.tanggal, 1, 10) = '{$todayStr}' and substr(schedules.jam_mulai, 1, 5) > '{$nowTimeStr}')) then schedules.id end) as available_slots"),
                ])
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
        });
    }

    /**
     * Count available slots on a specific date for a service.
     *
     * @param Tenant $tenant
     * @param Service $service
     * @param string $date (Y-m-d)
     * @return int
     */
    public function countAvailableSlotsOnDate(Tenant $tenant, Service $service, string $date): int
    {
        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);
        $isToday = Carbon::parse($date, $wib)->isSameDay($nowWib);

        $query = DB::table('schedules')
            ->leftJoin('bookings', function ($join) {
                $join->on('schedules.id', '=', 'bookings.idschedule')
                    ->where(function ($q) {
                        $q->whereIn('bookings.status', ['paid', 'completed'])
                          ->orWhere(function ($sub) {
                              $sub->where('bookings.status', 'pending')
                                  ->where('bookings.created_at', '>=', now()->subMinutes(15));
                          });
                    });
            })
            ->where('schedules.idtenant', $tenant->id)
            ->where('schedules.idlayanan', $service->id)
            ->where('schedules.status', 'tersedia')
            ->whereDate('schedules.tanggal', $date)
            ->whereNull('bookings.id');

        if ($isToday) {
            $nowTimeStr = $nowWib->format('H:i');
            $query->whereRaw("substr(schedules.jam_mulai, 1, 5) > ?", [$nowTimeStr]);
        }

        return $query->count();
    }

    /**
     * Get time slots payload for customer time selection on a specific date.
     *
     * @param Tenant $tenant
     * @param Service $service
     * @param string $date (Y-m-d)
     * @return array
     */
    public function getTimeSlots(Tenant $tenant, Service $service, string $date): array
    {
        $dateStr = Carbon::parse($date)->toDateString();
        $cacheKey = $this->availabilityCache->getSchedulesCacheKey((int) $tenant->id, (int) $service->id, $dateStr);

        $scheduleRows = Cache::remember($cacheKey, now()->addSeconds(300), function () use ($tenant, $service, $dateStr) {
            return DB::table('schedules')
                ->leftJoin('bookings', function ($join) {
                    $join->on('schedules.id', '=', 'bookings.idschedule')
                        ->where(function ($q) {
                            $q->whereIn('bookings.status', ['paid', 'completed'])
                              ->orWhere(function ($sub) {
                                  $sub->where('bookings.status', 'pending')
                                      ->where('bookings.created_at', '>=', now()->subMinutes(15));
                              });
                        });
                })
                ->where('schedules.idtenant', $tenant->id)
                ->where('schedules.idlayanan', $service->id)
                ->where('schedules.status', 'tersedia')
                ->whereDate('schedules.tanggal', $dateStr)
                ->groupBy('schedules.id', 'schedules.jam_mulai', 'schedules.jam_selesai', 'schedules.tanggal', 'schedules.harga_override')
                ->orderBy('schedules.jam_mulai')
                ->select([
                    'schedules.id',
                    'schedules.jam_mulai',
                    'schedules.jam_selesai',
                    'schedules.tanggal',
                    'schedules.harga_override',
                    DB::raw('count(bookings.id) as booking_count'),
                ])
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => (int) $row->id,
                        'jam_mulai' => $row->jam_mulai,
                        'jam_selesai' => $row->jam_selesai,
                        'tanggal' => $row->tanggal,
                        'harga_override' => $row->harga_override !== null ? (float) $row->harga_override : null,
                        'booking_count' => (int) $row->booking_count,
                    ];
                })
                ->all();
        });

        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);
        $selectedDateCarbon = Carbon::parse($dateStr, $wib);
        $isToday = $selectedDateCarbon->isSameDay($nowWib);
        $isPastDate = $selectedDateCarbon->lt($nowWib->copy()->startOfDay());

        return collect($scheduleRows)->map(function (array $row) use ($isToday, $isPastDate, $nowWib, $dateStr, $wib, $service) {
            $slotDateTime = Carbon::parse($dateStr . ' ' . $row['jam_mulai'], $wib);
            $hour = (int) $slotDateTime->format('H');
            $session = 'evening';

            if ($hour >= 5 && $hour <= 11) {
                $session = 'morning';
            } elseif ($hour >= 12 && $hour <= 17) {
                $session = 'afternoon';
            }

            $isPast = $isPastDate || ($isToday && $slotDateTime->lessThanOrEqualTo($nowWib));
            $isBooked = $row['booking_count'] > 0;
            $isAvailable = !$isPast && !$isBooked;
            $slotPrice = $row['harga_override'] !== null ? (float) $row['harga_override'] : (float) $service->harga;
            $durasiMenit = (int) ($service->durasi ?: 60);
            $slotEndDateTime = (clone $slotDateTime)->addMinutes($durasiMenit);
            $timeRangeLabel = $slotDateTime->format('H:i') . ' – ' . $slotEndDateTime->format('H:i');

            return [
                'id' => $row['id'],
                'time' => $slotDateTime->format('H:i'),
                'label' => $timeRangeLabel,
                'start_time' => $slotDateTime->format('H:i'),
                'end_time' => $slotEndDateTime->format('H:i'),
                'range_label' => $timeRangeLabel,
                'time_range' => $timeRangeLabel,
                'period' => 'WIB',
                'session' => $session,
                'price' => $slotPrice,
                'price_label' => 'Rp ' . number_format($slotPrice, 0, ',', '.'),
                'is_available' => $isAvailable,
                'is_disabled' => !$isAvailable,
                'is_booked' => $isBooked,
                'is_past' => $isPast,
            ];
        })->values()->all();
    }

    /**
     * Get available slots for reschedule / owner booking.
     *
     * @param Tenant $tenant
     * @param int $serviceId
     * @param string $date (Y-m-d)
     * @param int|null $excludeBookingId
     * @return Collection
     */
    public function getSlotsForReschedule(
        Tenant $tenant,
        int $serviceId,
        string $date,
        ?int $excludeBookingId = null
    ): Collection {
        $slots = Schedule::withoutGlobalScopes()
            ->where('idtenant', $tenant->id)
            ->where('idlayanan', $serviceId)
            ->whereDate('tanggal', $date)
            ->where('status', 'tersedia')
            ->orderBy('jam_mulai')
            ->get();

        $takenSlotIds = Booking::withoutGlobalScopes()
            ->where('idtenant', $tenant->id)
            ->whereDate('tanggalbooking', $date)
            ->whereIn('status', ['pending', 'paid', 'completed'])
            ->when($excludeBookingId !== null, fn($q) => $q->where('id', '!=', $excludeBookingId))
            ->pluck('idschedule')
            ->filter()
            ->toArray();

        return $slots->map(function ($slot) use ($takenSlotIds, $excludeBookingId) {
            $isOccupied = in_array($slot->id, $takenSlotIds, true);
            $isCurrentSlot = ($excludeBookingId !== null && $slot->bookings()->where('id', $excludeBookingId)->exists());

            return [
                'id' => $slot->id,
                'schedule' => $slot,
                'jam_mulai' => substr($slot->jam_mulai, 0, 5),
                'jam_selesai' => substr($slot->jam_selesai, 0, 5),
                'is_available' => !$isOccupied,
                'is_current' => $isCurrentSlot,
            ];
        });
    }

    /**
     * Get available time slots for customer reschedule flow (AJAX).
     *
     * @param Tenant $tenant
     * @param Service $service
     * @param string $selectedDate (Y-m-d)
     * @param int $excludeBookingId
     * @return array<int, array{id: int, jam_mulai: string, jam_selesai: string, is_available: bool, is_booked: bool, is_past: bool}>
     */
    public function getCustomerRescheduleSlots(
        Tenant $tenant,
        Service $service,
        string $selectedDate,
        int $excludeBookingId
    ): array {
        $cacheKey = "tenant:{$tenant->id}:service:{$service->id}:schedules:{$selectedDate}:reschedule:{$excludeBookingId}";

        return Cache::remember($cacheKey, now()->addSeconds(300), function () use ($tenant, $service, $selectedDate, $excludeBookingId) {
            return DB::table('schedules')
                ->leftJoin('bookings', function ($join) use ($excludeBookingId) {
                    $join->on('schedules.id', '=', 'bookings.idschedule')
                        ->whereIn('bookings.status', ['pending', 'paid', 'completed'])
                        ->where('bookings.id', '!=', $excludeBookingId);
                })
                ->where('schedules.idtenant', $tenant->id)
                ->where('schedules.idlayanan', $service->id)
                ->where('schedules.status', 'tersedia')
                ->whereDate('schedules.tanggal', $selectedDate)
                ->groupBy('schedules.id', 'schedules.jam_mulai', 'schedules.jam_selesai', 'schedules.tanggal')
                ->orderBy('schedules.jam_mulai')
                ->select([
                    'schedules.id',
                    'schedules.jam_mulai',
                    'schedules.jam_selesai',
                    DB::raw('count(bookings.id) as booking_count'),
                ])
                ->get()
                ->map(function ($row) use ($selectedDate) {
                    $wib = 'Asia/Jakarta';
                    $nowWib = Carbon::now($wib);
                    $selectedDateCarbon = Carbon::parse($selectedDate, $wib);
                    $isToday = $selectedDateCarbon->isSameDay($nowWib);
                    $isPastDate = $selectedDateCarbon->lt($nowWib->copy()->startOfDay());
                    $slotDateTime = Carbon::parse($selectedDate . ' ' . $row->jam_mulai, $wib);
                    $isBooked    = $row->booking_count > 0;
                    $isPast      = $isPastDate || ($isToday && $slotDateTime->lessThanOrEqualTo($nowWib));
                    $isAvailable = !$isBooked && !$isPast;

                    return [
                        'id'           => (int) $row->id,
                        'jam_mulai'    => $slotDateTime->format('H:i'),
                        'jam_selesai'  => Carbon::parse($selectedDate . ' ' . $row->jam_selesai, $wib)->format('H:i'),
                        'is_available' => $isAvailable,
                        'is_booked'    => $isBooked,
                        'is_past'      => $isPast,
                    ];
                })
                ->all();
        });
    }
}

