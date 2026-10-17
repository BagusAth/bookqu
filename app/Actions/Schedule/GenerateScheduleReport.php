<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GenerateScheduleReport
{
    /**
     * Generate operational metrics, hourly/daily workload, and utilization report data.
     *
     * @return array<string, mixed>
     */
    public function execute(Tenant $tenant, Request $request): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $period = (string) $request->query('period', 'all');
        $now = Carbon::now();
        $startDate = null;
        $endDate = null;

        if ($period === 'today') {
            $startDate = $now->copy()->startOfDay()->toDateString();
            $endDate = $now->copy()->endOfDay()->toDateString();
        } elseif ($period === 'this_week') {
            $startDate = $now->copy()->startOfWeek()->toDateString();
            $endDate = $now->copy()->endOfWeek()->toDateString();
        } elseif ($period === 'this_month') {
            $startDate = $now->copy()->startOfMonth()->toDateString();
            $endDate = $now->copy()->endOfMonth()->toDateString();
        } elseif ($period === 'last_30_days') {
            $startDate = $now->copy()->subDays(30)->toDateString();
            $endDate = $now->copy()->toDateString();
        }

        $schedulesQuery = Schedule::where('idtenant', $tenant->id);
        if ($startDate && $endDate) {
            $schedulesQuery->whereBetween('tanggal', [$startDate, $endDate]);
        }

        $totalSlots = (clone $schedulesQuery)->count();
        $bookedSlots = (clone $schedulesQuery)
            ->whereHas('bookings', fn($q) => $q->whereIn('status', ['paid', 'completed']))
            ->count();
        $availableSlots = (clone $schedulesQuery)
            ->where('status', 'tersedia')
            ->whereDoesntHave('bookings', fn($q) => $q->whereIn('status', ['pending', 'paid', 'completed']))
            ->count();
        $utilizationRate = $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100, 1) : 0;

        $bookingsQuery = Booking::where('idtenant', $tenant->id)
            ->whereIn('status', ['paid', 'completed'])
            ->with('layanan');

        if ($startDate && $endDate) {
            $bookingsQuery->whereBetween('tanggalbooking', [$startDate, $endDate]);
        }

        $bookings = $bookingsQuery->get();

        // Hourly distribution
        $hourlyCounts = [];
        for ($h = 8; $h <= 21; $h++) {
            $formattedH = sprintf('%02d:00', $h);
            $hourlyCounts[$formattedH] = 0;
        }

        foreach ($bookings as $b) {
            if ($b->jam) {
                $h = substr($b->jam, 0, 2) . ':00';
                if (isset($hourlyCounts[$h])) {
                    $hourlyCounts[$h]++;
                }
            }
        }

        // Peak and low demand hours
        $sortedHours = $hourlyCounts;
        arsort($sortedHours);
        $hasHourData = !empty($sortedHours) && reset($sortedHours) > 0;
        $peakHour = $hasHourData ? array_key_first($sortedHours) : 'Tidak ada data';
        $lowHour = $hasHourData ? array_key_last($sortedHours) : 'Tidak ada data';

        // Daily distribution (1=Monday .. 7=Sunday)
        $dailyCounts = [
            'Senin'  => 0,
            'Selasa' => 0,
            'Rabu'   => 0,
            'Kamis'  => 0,
            'Jumat'  => 0,
            'Sabtu'  => 0,
            'Minggu' => 0,
        ];

        $dayMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        foreach ($bookings as $b) {
            if ($b->tanggalbooking) {
                $dayOfWeek = Carbon::parse($b->tanggalbooking)->dayOfWeekIso;
                if (isset($dayMap[$dayOfWeek])) {
                    $dailyCounts[$dayMap[$dayOfWeek]]++;
                }
            }
        }

        $sortedDays = $dailyCounts;
        arsort($sortedDays);
        $hasDayData = !empty($sortedDays) && reset($sortedDays) > 0;
        $peakDay = $hasDayData ? array_key_first($sortedDays) : 'Tidak ada data';
        $lowDay = $hasDayData ? array_key_last($sortedDays) : 'Tidak ada data';

        // Staff workload based on actual assigned service bookings
        $staffMembers = Staff::where('idtenant', $tenant->id)->with('services')->get()->map(function ($s) use ($bookings) {
            $serviceIds = $s->services->pluck('id')->toArray();
            $handledBookingsCount = !empty($serviceIds)
                ? $bookings->whereIn('idlayanan', $serviceIds)->count()
                : 0;
            return [
                'name'           => $s->name,
                'role'           => $s->role,
                'services_count' => count($serviceIds),
                'services_names' => $s->services->pluck('namalayanan')->implode(', '),
                'count'          => $handledBookingsCount,
            ];
        });

        // Resource workload based on actual assigned service bookings
        $resourceList = Resource::where('idtenant', $tenant->id)->with('services')->get()->map(function ($r) use ($bookings) {
            $serviceIds = $r->services->pluck('id')->toArray();
            $handledBookingsCount = !empty($serviceIds)
                ? $bookings->whereIn('idlayanan', $serviceIds)->count()
                : 0;
            return [
                'name'           => $r->name,
                'type'           => $r->type,
                'capacity'       => $r->capacity,
                'services_count' => count($serviceIds),
                'services_names' => $r->services->pluck('namalayanan')->implode(', '),
                'count'          => $handledBookingsCount,
            ];
        });

        return [
            'tenant'          => $tenant,
            'totalSlots'      => $totalSlots,
            'bookedSlots'     => $bookedSlots,
            'availableSlots'  => $availableSlots,
            'utilizationRate' => $utilizationRate,
            'hourlyCounts'    => $hourlyCounts,
            'peakHour'        => $peakHour,
            'lowHour'         => $lowHour,
            'dailyCounts'     => $dailyCounts,
            'peakDay'         => $peakDay,
            'lowDay'          => $lowDay,
            'staffMembers'    => $staffMembers,
            'resourceList'    => $resourceList,
            'period'          => $period,
        ];
    }
}
