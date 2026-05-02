<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $currentStart = $now->copy()->subDays(6)->startOfDay();
        $previousStart = $now->copy()->subDays(13)->startOfDay();
        $previousEnd = $now->copy()->subDays(7)->endOfDay();

        $totalBookings = DB::table('bookings')->count();
        $totalRevenue = DB::table('payments')->where('status', 'sukses')->sum('jumlah');
        $totalPrograms = DB::table('services')->count();

        $bookingsCurrent = DB::table('bookings')
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();
        $bookingsPrevious = DB::table('bookings')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $revenueCurrent = DB::table('payments')
            ->where('status', 'sukses')
            ->whereBetween('created_at', [$currentStart, $now])
            ->sum('jumlah');
        $revenuePrevious = DB::table('payments')
            ->where('status', 'sukses')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('jumlah');

        $servicesCurrent = DB::table('services')
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();
        $servicesPrevious = DB::table('services')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $trialSubscriptions = DB::table('subscriptions')
            ->where('status', 'trial')
            ->get(['trial_berakhir']);
        $trialCount = $trialSubscriptions->count();
        $trialDaysRemaining = 0;
        if ($trialCount > 0) {
            $nearestTrialEnd = $trialSubscriptions
                ->filter(fn ($row) => !empty($row->trial_berakhir))
                ->map(fn ($row) => Carbon::parse($row->trial_berakhir))
                ->sort()
                ->first();

            if ($nearestTrialEnd) {
                $trialDaysRemaining = max(0, $now->diffInDays($nearestTrialEnd, false));
            }
        }

        $summaryCards = [
            [
                'title' => 'Total Bookings',
                'value' => number_format($totalBookings),
                'trend' => $this->formatTrend($bookingsCurrent, $bookingsPrevious),
                'icon' => 'calendar',
                'icon_bg' => 'bg-indigo-50',
                'icon_color' => 'text-indigo-600',
                'trend_variant' => $this->trendVariant($bookingsCurrent, $bookingsPrevious),
            ],
            [
                'title' => 'Total Revenue',
                'value' => 'Rp '.number_format($totalRevenue),
                'trend' => $this->formatTrend($revenueCurrent, $revenuePrevious),
                'icon' => 'banknote',
                'icon_bg' => 'bg-amber-50',
                'icon_color' => 'text-amber-600',
                'trend_variant' => $this->trendVariant($revenueCurrent, $revenuePrevious),
            ],
            [
                'title' => 'Active Programs',
                'value' => number_format($totalPrograms),
                'trend' => $this->formatTrend($servicesCurrent, $servicesPrevious),
                'icon' => 'puzzle',
                'icon_bg' => 'bg-blue-50',
                'icon_color' => 'text-blue-600',
                'trend_variant' => $this->trendVariant($servicesCurrent, $servicesPrevious),
            ],
        ];

        $trendColors = [
            ['bg' => 'bg-emerald-50', 'color' => 'text-emerald-500', 'icon' => 'trending'],
            ['bg' => 'bg-blue-50', 'color' => 'text-blue-500', 'icon' => 'arrow'],
            ['bg' => 'bg-amber-50', 'color' => 'text-amber-500', 'icon' => 'sparkles'],
        ];

        $dailyTrends = DB::table('bookings')
            ->join('services', 'bookings.idlayanan', '=', 'services.id')
            ->whereBetween('bookings.created_at', [$currentStart, $now])
            ->select('services.namalayanan', DB::raw('count(*) as total'))
            ->groupBy('services.namalayanan')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->values()
            ->map(function ($row, $index) use ($trendColors) {
                $palette = $trendColors[$index % count($trendColors)];

                return [
                    'title' => $row->namalayanan,
                    'subtitle' => $row->total.' booking 7 hari terakhir',
                    'value' => number_format($row->total),
                    'icon' => $palette['icon'],
                    'icon_bg' => $palette['bg'],
                    'icon_color' => $palette['color'],
                ];
            })
            ->all();

        $recentActivities = DB::table('bookings')
            ->join('services', 'bookings.idlayanan', '=', 'services.id')
            ->orderByDesc('bookings.created_at')
            ->limit(6)
            ->get(['services.namalayanan', 'bookings.status'])
            ->map(function ($row) {
                $statusMap = [
                    'paid' => 'confirmed',
                    'completed' => 'confirmed',
                    'pending' => 'pending',
                    'cancelled' => 'cancelled',
                ];

                return [
                    'program' => $row->namalayanan,
                    'status' => $statusMap[$row->status] ?? 'pending',
                ];
            })
            ->all();

        $months = collect(range(6, 0))
            ->map(fn ($offset) => $now->copy()->subMonths($offset)->startOfMonth());
        $revenuePeriods = $months->map(fn ($month) => strtoupper($month->format('M')))->all();
        $revenueSeries = $months->map(function ($month) {
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            return (float) DB::table('payments')
                ->where('status', 'sukses')
                ->whereBetween('created_at', [$start, $end])
                ->sum('jumlah');
        })->all();

        $user = Auth::user();
        $userName = $user?->namalengkap ?? 'Admin';
        $userSubtitle = $trialCount > 0
            ? 'Ada '.$trialCount.' tenant dalam masa trial.'
            : 'Tidak ada tenant trial saat ini.';

        return view('admin.dashboard', [
            'title' => 'Dashboard',
            'menuItems' => [
                ['name' => 'Dashboard', 'icon' => 'dashboard', 'url' => route('admin.dashboard')],
                ['name' => 'Tenants', 'icon' => 'building', 'url' => route('admin.tenants')],
                ['name' => 'Subscriptions', 'icon' => 'credit-card', 'url' => route('admin.subscriptions')],
            ],
            'activeMenu' => 'Dashboard',
            'userName' => $userName,
            'userSubtitle' => $userSubtitle,
            'avatarUrl' => 'https://i.pravatar.cc/150?u=bookqu-admin',
            'trialDaysRemaining' => $trialDaysRemaining,
            'trialCount' => $trialCount,
            'summaryCards' => $summaryCards,
            'revenuePeriods' => $revenuePeriods,
            'revenueSeries' => $revenueSeries,
            'dailyTrends' => $dailyTrends,
            'recentActivities' => $recentActivities,
        ]);
    }

    private function formatTrend(float|int $current, float|int $previous): string
    {
        if ($previous <= 0) {
            return $current > 0 ? '+100%' : '0%';
        }

        $change = (($current - $previous) / $previous) * 100;
        $sign = $change >= 0 ? '+' : '';

        return $sign.number_format($change, 1).'%';
    }

    private function trendVariant(float|int $current, float|int $previous): string
    {
        if ($current === $previous) {
            return 'neutral';
        }

        return $current > $previous ? 'success' : 'neutral';
    }
}
