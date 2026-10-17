<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GetOwnerCalendarData
{
    /**
     * Compute calendar views, schedules, bookings, and navigation dates.
     *
     * @return array<string, mixed>
     */
    public function execute(Tenant $tenant, Request $request): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $view = in_array($request->query('view'), ['day', 'week', 'month'], true)
            ? (string) $request->query('view')
            : 'week';

        $rawDate = $request->query('date');
        $currentDate = $rawDate ? Carbon::parse((string) $rawDate) : Carbon::today();

        // Calculate navigation dates based on active viewMode
        $todayDate = Carbon::today()->toDateString();
        if ($view === 'day') {
            $prevDate = $currentDate->copy()->subDay()->toDateString();
            $nextDate = $currentDate->copy()->addDay()->toDateString();
            $dateLabel = $currentDate->translatedFormat('l, d F Y');
            $weekStart = null;
            $weekEnd = null;
        } elseif ($view === 'month') {
            $prevDate = $currentDate->copy()->subMonth()->startOfMonth()->toDateString();
            $nextDate = $currentDate->copy()->addMonth()->startOfMonth()->toDateString();
            $dateLabel = $currentDate->translatedFormat('F Y');
            $weekStart = null;
            $weekEnd = null;
        } else { // week
            $startOfWeek = $currentDate->copy()->startOfWeek();
            $endOfWeek = $currentDate->copy()->endOfWeek();
            $prevDate = $startOfWeek->copy()->subWeek()->toDateString();
            $nextDate = $startOfWeek->copy()->addWeek()->toDateString();
            $dateLabel = $startOfWeek->translatedFormat('d M') . ' - ' . $endOfWeek->translatedFormat('d M Y');
            $weekStart = $startOfWeek;
            $weekEnd = $endOfWeek;
        }

        $selectedService = $request->query('service_id', 'all');
        $selectedStatus = $request->query('status', 'all');

        $services = Service::where('idtenant', $tenant->id)->orderBy('namalayanan')->get();

        // Date range covering month + edge weeks for smooth view transitions
        $rangeStart = $currentDate->copy()->startOfMonth()->startOfWeek();
        $rangeEnd = $currentDate->copy()->endOfMonth()->endOfWeek();

        // Blocked dates in range
        $blockedDates = OwnerBlockedDate::where('idtenant', $tenant->id)
            ->whereBetween('tanggal', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->pluck('alasan', 'tanggal')
            ->toArray();

        // Schedules in range
        $schedulesQuery = Schedule::where('idtenant', $tenant->id)
            ->whereBetween('tanggal', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with(['layanan', 'bookings.payment']);

        if ($selectedService !== 'all' && is_numeric($selectedService)) {
            $schedulesQuery->where('idlayanan', (int) $selectedService);
        }

        if ($selectedStatus === 'available') {
            $schedulesQuery->where('status', 'tersedia')
                ->whereDoesntHave('bookings', fn($q) => $q->whereIn('status', ['pending', 'paid', 'completed']));
        } elseif ($selectedStatus === 'blocked') {
            $schedulesQuery->where('status', 'diblokir');
        } elseif ($selectedStatus !== 'all' && in_array($selectedStatus, ['paid', 'pending', 'completed', 'cancelled'], true)) {
            $schedulesQuery->whereHas('bookings', fn($q) => $q->where('status', $selectedStatus));
        }

        $schedules = $schedulesQuery->orderBy('tanggal')->orderBy('jam_mulai')->get();

        // Bookings in range
        $bookingsQuery = Booking::where('idtenant', $tenant->id)
            ->whereBetween('tanggalbooking', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with(['layanan', 'payment', 'schedule']);

        if ($selectedService !== 'all' && is_numeric($selectedService)) {
            $bookingsQuery->where('idlayanan', (int) $selectedService);
        }

        if ($selectedStatus === 'available' || $selectedStatus === 'blocked') {
            $bookingsQuery->whereRaw('1 = 0');
        } elseif ($selectedStatus !== 'all' && in_array($selectedStatus, ['paid', 'pending', 'completed', 'cancelled'], true)) {
            $bookingsQuery->where('status', $selectedStatus);
        }

        $bookings = $bookingsQuery->orderBy('tanggalbooking')->orderBy('jam')->get();

        // Week schedules specific for week view
        $wStart = $currentDate->copy()->startOfWeek();
        $wEnd = $currentDate->copy()->endOfWeek();
        $weekSchedules = $schedules->filter(function ($s) use ($wStart, $wEnd) {
            $t = Carbon::parse($s->tanggal);
            return $t->betweenIncluded($wStart, $wEnd);
        })->values();

        // Month bookings grouped by day (1..31) for month view
        $monthBookings = $bookings->filter(function ($b) use ($currentDate) {
            $t = Carbon::parse($b->tanggalbooking);
            return $t->month === $currentDate->month && $t->year === $currentDate->year;
        })->groupBy(fn($b) => Carbon::parse($b->tanggalbooking)->format('j'));

        // Month schedules grouped by day (1..31) for month view
        $monthSchedules = $schedules->filter(function ($s) use ($currentDate) {
            $t = Carbon::parse($s->tanggal);
            return $t->month === $currentDate->month && $t->year === $currentDate->year;
        })->groupBy(fn($s) => Carbon::parse($s->tanggal)->format('j'));

        return [
            'tenant'          => $tenant,
            'services'        => $services,
            'bookings'        => $bookings,
            'schedules'       => $schedules,
            'weekSchedules'   => $weekSchedules,
            'monthBookings'   => $monthBookings,
            'monthSchedules'  => $monthSchedules,
            'blockedDates'    => $blockedDates,
            'view'            => $view,
            'currentDate'     => $currentDate,
            'prevDate'        => $prevDate,
            'nextDate'        => $nextDate,
            'todayDate'       => $todayDate,
            'dateLabel'       => $dateLabel,
            'weekStart'       => $weekStart,
            'weekEnd'         => $weekEnd,
            'selectedService' => $selectedService,
            'selectedStatus'  => $selectedStatus,
        ];
    }
}
