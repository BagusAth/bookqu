<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalBookings = DB::table('bookings')->count();
        $totalRevenue = DB::table('payments')->where('status', 'sukses')->sum('jumlah');
        $totalPrograms = DB::table('services')->count();
        $trialCount = DB::table('subscriptions')->where('status', 'trial')->count();

        $summaryCards = [
            [
                'title' => 'Total Bookings',
                'value' => number_format($totalBookings),
                'trend' => '+12%',
                'icon' => 'calendar',
                'icon_bg' => 'bg-indigo-50',
                'icon_color' => 'text-indigo-600',
                'trend_variant' => 'success',
            ],
            [
                'title' => 'Total Revenue',
                'value' => 'Rp '.number_format($totalRevenue),
                'trend' => '+8%',
                'icon' => 'banknote',
                'icon_bg' => 'bg-amber-50',
                'icon_color' => 'text-amber-600',
                'trend_variant' => 'success',
            ],
            [
                'title' => 'Active Programs',
                'value' => number_format($totalPrograms),
                'trend' => 'Steady',
                'icon' => 'puzzle',
                'icon_bg' => 'bg-blue-50',
                'icon_color' => 'text-blue-600',
                'trend_variant' => 'neutral',
            ],
        ];

        $dailyTrends = [
            [
                'title' => 'Morning Yoga Session',
                'subtitle' => '+15% vs yesterday',
                'value' => '128',
                'icon' => 'trending',
                'icon_bg' => 'bg-emerald-50',
                'icon_color' => 'text-emerald-500',
            ],
            [
                'title' => 'Advanced Pilates',
                'subtitle' => 'Stable performance',
                'value' => '84',
                'icon' => 'arrow',
                'icon_bg' => 'bg-blue-50',
                'icon_color' => 'text-blue-500',
            ],
        ];

        $recentActivities = [
            [
                'program' => 'Premium Yoga Flow',
                'status' => 'confirmed',
            ],
            [
                'program' => 'Beginner Pilates',
                'status' => 'pending',
            ],
            [
                'program' => 'HIIT Cardio Max',
                'status' => 'confirmed',
            ],
        ];

        return view('admin.dashboard', [
            'title' => 'Dashboard',
            'menuItems' => [
                ['name' => 'Dashboard', 'icon' => 'dashboard', 'url' => route('admin.dashboard')],
                ['name' => 'Programs', 'icon' => 'calendar-days', 'url' => '#'],
                ['name' => 'Schedule', 'icon' => 'calendar', 'url' => '#'],
                ['name' => 'Bookings', 'icon' => 'book-check', 'url' => '#'],
                ['name' => 'Analytics', 'icon' => 'bar-chart', 'url' => '#'],
                ['name' => 'Subscription', 'icon' => 'credit-card', 'url' => '#'],
            ],
            'activeMenu' => 'Dashboard',
            'userName' => 'Alex',
            'userSubtitle' => 'Here\'s what\'s happening with your business today.',
            'avatarUrl' => 'https://i.pravatar.cc/150?u=bookqu-admin',
            'trialDaysRemaining' => 7,
            'trialCount' => $trialCount,
            'summaryCards' => $summaryCards,
            'revenuePeriods' => ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL'],
            'dailyTrends' => $dailyTrends,
            'recentActivities' => $recentActivities,
        ]);
    }
}
