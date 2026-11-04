<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GetOwnerAnalyticsTrends
{
    /**
     * Monthly revenue and booking counts for the last 12 months and daily booking counts for
     * the last 30 days, each from one grouped query (BQ-111, NFR-PERF-004).
     *
     * Revenue counts successful booking payments by `created_at`; bookings count by
     * `tanggalbooking`, as before. The period is computed in a derived table so MariaDB's
     * ONLY_FULL_GROUP_BY accepts the grouping.
     *
     * @return array{
     *     labelbulanpanjang: list<string>,
     *     revenueperbulan: list<float>,
     *     bookingperbulan: list<int>,
     *     labelhari: list<string>,
     *     bookingperhari: list<int>
     * }
     */
    public function execute(int $tenantId): array
    {
        $now = Carbon::now();
        $firstMonth = $now->copy()->subMonths(11)->startOfMonth();
        $lastMonthEnd = $now->copy()->endOfMonth();
        $firstDay = $now->copy()->subDays(29)->startOfDay();

        $revenueByMonth = $this->grouped(
            Payment::where('idtenant', $tenantId)
                ->where('tipe', 'booking')
                ->where('status', 'sukses')
                ->whereBetween('created_at', [$firstMonth, $lastMonthEnd]),
            $this->monthExpression('created_at'),
            'SUM(jumlah)',
            'jumlah',
        );

        $bookingsByMonth = $this->grouped(
            Booking::where('idtenant', $tenantId)
                ->whereDate('tanggalbooking', '>=', $firstMonth->format('Y-m-d'))
                ->whereDate('tanggalbooking', '<=', $lastMonthEnd->format('Y-m-d')),
            $this->monthExpression('tanggalbooking'),
            'COUNT(*)',
        );

        $bookingsByDay = $this->grouped(
            Booking::where('idtenant', $tenantId)
                ->whereDate('tanggalbooking', '>=', $firstDay->format('Y-m-d'))
                ->whereDate('tanggalbooking', '<=', $now->format('Y-m-d')),
            $this->dayExpression('tanggalbooking'),
            'COUNT(*)',
        );

        $labelbulanpanjang = [];
        $revenueperbulan = [];
        $bookingperbulan = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i)->startOfMonth();
            $key = $month->format('Y-m');
            $labelbulanpanjang[] = $month->format('M Y');
            $revenueperbulan[] = round((float) ($revenueByMonth[$key] ?? 0));
            $bookingperbulan[] = (int) ($bookingsByMonth[$key] ?? 0);
        }

        $labelhari = [];
        $bookingperhari = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $labelhari[] = $day->format('d M');
            $bookingperhari[] = (int) ($bookingsByDay[$day->format('Y-m-d')] ?? 0);
        }

        return compact('labelbulanpanjang', 'revenueperbulan', 'bookingperbulan', 'labelhari', 'bookingperhari');
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $rows
     * @return array<string, float|int|string>
     */
    private function grouped(Builder $rows, string $periodExpression, string $aggregate, ?string $valueColumn = null): array
    {
        $inner = $rows->toBase()->selectRaw($periodExpression.' as periode'.($valueColumn !== null ? ', '.$valueColumn : ''));

        return DB::query()
            ->fromSub($inner, 'baris')
            ->selectRaw('periode, '.$aggregate.' as nilai')
            ->groupBy('periode')
            ->pluck('nilai', 'periode')
            ->all();
    }

    private function monthExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function dayExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m-%d')";
    }
}
