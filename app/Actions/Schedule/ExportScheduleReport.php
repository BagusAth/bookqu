<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\Schedule;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportScheduleReport
{
    /**
     * Export schedule and slot utilization data as a downloadable CSV stream.
     */
    public function execute(Tenant $tenant, Request $request): StreamedResponse
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

        $schedulesQuery = Schedule::where('idtenant', $tenant->id)->with(['layanan', 'bookings']);
        if ($startDate && $endDate) {
            $schedulesQuery->whereBetween('tanggal', [$startDate, $endDate]);
        }
        $schedules = $schedulesQuery->orderBy('tanggal')->orderBy('jam_mulai')->get();

        $totalSlots = $schedules->count();
        $bookedSlots = $schedules->filter(fn($s) => $s->bookings->whereIn('status', ['paid', 'completed'])->isNotEmpty())->count();
        $availableSlots = $schedules->filter(fn($s) => $s->status === 'tersedia' && $s->bookings->whereIn('status', ['pending', 'paid', 'completed'])->isEmpty())->count();
        $utilizationRate = $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100, 1) : 0;

        $filename = 'schedule-report-' . ($tenant->slug ?? 'tenant') . '-' . $period . '-' . date('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tenant, $schedules, $totalSlots, $bookedSlots, $availableSlots, $utilizationRate, $period) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['BOOKQU - SCHEDULE & UTILIZATION REPORT']);
            fputcsv($file, ['Bisnis', $tenant->namabisnis ?? '-']);
            fputcsv($file, ['Periode', ucfirst($period)]);
            fputcsv($file, ['Tanggal Ekspor', date('Y-m-d H:i:s')]);
            fputcsv($file, []);
            fputcsv($file, ['METRIK KPI', 'NILAI']);
            fputcsv($file, ['Total Slots', $totalSlots]);
            fputcsv($file, ['Booked Slots', $bookedSlots]);
            fputcsv($file, ['Available Slots', $availableSlots]);
            fputcsv($file, ['Utilization Rate (%)', $utilizationRate . '%']);
            fputcsv($file, []);
            fputcsv($file, ['RINCIAN SLOT OPERASIONAL']);
            fputcsv($file, ['Tanggal', 'Jam Mulai', 'Jam Selesai', 'Layanan', 'Tarif (Rp)', 'Status Availability', 'Nama Tamu / Pemesan']);

            foreach ($schedules as $s) {
                $b = $s->bookings->whereIn('status', ['paid', 'completed'])->first();
                fputcsv($file, [
                    $s->tanggal,
                    substr($s->jam_mulai, 0, 5),
                    substr($s->jam_selesai, 0, 5),
                    $s->layanan->namalayanan ?? '-',
                    $s->harga_override ?? $s->layanan->harga ?? 0,
                    $s->getAvailabilityStatus(),
                    $b ? $b->namapelanggan : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
