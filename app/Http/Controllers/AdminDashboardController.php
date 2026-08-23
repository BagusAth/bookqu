<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // ── Statistik Global ──
        $totalTenant      = Tenant::count();
        $totalUser        = User::where('role', 'owner')->count();
        $tenantTrial      = Subscription::where('status', 'trial')->count();
        $tenantAktif      = Subscription::where('status', 'active')->count();
        $tenantExpired    = Subscription::where('status', 'expired')->count();

        // ── Revenue platform dari langganan ──
        $totalRevenuePlatform = Payment::where('tipe', 'subscription')
            ->where('status', 'sukses')
            ->sum('jumlah');

        $revenuebulanini = Payment::where('tipe', 'subscription')
            ->where('status', 'sukses')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('jumlah');

        // ── Daftar tenant terbaru ──
        $tenantTerbaru = Tenant::with(['user', 'subscriptions' => function ($q) {
            $q->latest()->limit(1);
        }])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // ── Grafik revenue langganan 6 bulan ──
        $labelBulan = [];
        $revenuePerBulan = [];
        for ($i = 5; $i >= 0; $i--) {
            $awal  = Carbon::now()->subMonths($i)->startOfMonth();
            $akhir = Carbon::now()->subMonths($i)->endOfMonth();
            $labelBulan[]      = $awal->format('M Y');
            $revenuePerBulan[] = round(Payment::where('tipe', 'subscription')
                ->where('status', 'sukses')
                ->whereBetween('created_at', [$awal, $akhir])
                ->sum('jumlah'));
        }

        return view('admin.dashboard', compact(
            'totalTenant',
            'totalUser',
            'tenantTrial',
            'tenantAktif',
            'tenantExpired',
            'totalRevenuePlatform',
            'revenuebulanini',
            'tenantTerbaru',
            'labelBulan',
            'revenuePerBulan',
        ));
    }
}
