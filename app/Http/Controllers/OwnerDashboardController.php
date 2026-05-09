<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OwnerDashboardController extends Controller
{
    public function index()
    {
        $iduser = Auth::id();
        $tenant = Tenant::with('user')->where('iduser', $iduser)->first();

        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan untuk akun ini.');
        }

        $idtenant = $tenant->id;
        $bulanini = Carbon::now()->startOfMonth();
        $bulanlalu = Carbon::now()->subMonth()->startOfMonth();
        $akhirbulanlalu = Carbon::now()->subMonth()->endOfMonth();

        // ── Total Booking ──
        $totalbooking = Booking::where('idtenant', $idtenant)->count();

        $bookinbulanini = Booking::where('idtenant', $idtenant)
            ->where('tanggalbooking', '>=', $bulanini)
            ->count();

        $bookinbulanlalu = Booking::where('idtenant', $idtenant)
            ->whereBetween('tanggalbooking', [$bulanlalu, $akhirbulanlalu])
            ->count();

        $persenperubahanboking = $bookinbulanlalu > 0
            ? round((($bookinbulanini - $bookinbulanlalu) / $bookinbulanlalu) * 100)
            : 0;

        // ── Total Revenue ──
        $totalrevenue = Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->sum('jumlah');

        $revenuebulanini = Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->where('created_at', '>=', $bulanini)
            ->sum('jumlah');

        $revenuebulanlalu = Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->whereBetween('created_at', [$bulanlalu, $akhirbulanlalu])
            ->sum('jumlah');

        $persenperubahanrevenue = $revenuebulanlalu > 0
            ? round((($revenuebulanini - $revenuebulanlalu) / $revenuebulanlalu) * 100)
            : 0;

        // ── Active Programs ──
        $programaktif = Service::where('idtenant', $idtenant)->count();

        // ── Revenue Per Bulan (7 bulan terakhir) ──
        $datarevenueperbulan = [];
        $labelbulan = [];
        for ($i = 6; $i >= 0; $i--) {
            $awaltarget = Carbon::now()->subMonths($i)->startOfMonth();
            $akhirtarget = Carbon::now()->subMonths($i)->endOfMonth();

            $labelbulan[] = $awaltarget->format('M');

            $revenuenya = Payment::where('idtenant', $idtenant)
                ->where('tipe', 'booking')
                ->where('status', 'sukses')
                ->whereBetween('created_at', [$awaltarget, $akhirtarget])
                ->sum('jumlah');

            $datarevenueperbulan[] = round($revenuenya);
        }

        // ── Daily Trends (layanan terpopuler hari ini vs kemarin) ──
        $hariini = Carbon::today();
        $kemarin = Carbon::yesterday();

        $trendlayanan = Service::where('services.idtenant', $idtenant)
            ->select('services.id', 'services.namalayanan')
            ->withCount(['bookings as bookingshariini' => function ($query) use ($hariini) {
                $query->whereDate('tanggalbooking', $hariini);
            }])
            ->withCount(['bookings as bookingskemarin' => function ($query) use ($kemarin) {
                $query->whereDate('tanggalbooking', $kemarin);
            }])
            ->orderByDesc('bookingshariini')
            ->limit(5)
            ->get()
            ->map(function ($layanan) {
                $perubahan = $layanan->bookingskemarin > 0
                    ? round((($layanan->bookingshariini - $layanan->bookingskemarin) / $layanan->bookingskemarin) * 100)
                    : 0;

                return [
                    'namalayanan' => $layanan->namalayanan,
                    'jumlahbooking' => $layanan->bookingshariini,
                    'persenperubahan' => $perubahan,
                    'trennya' => $perubahan > 0 ? 'naik' : ($perubahan < 0 ? 'turun' : 'stabil'),
                ];
            });

        // ── Recent Activity ──
        $aktivitasterbaru = Booking::where('bookings.idtenant', $idtenant)
            ->with('layanan')
            ->orderByDesc('bookings.created_at')
            ->limit(10)
            ->get();

        // ── Subscription / Trial ──
        $langganan = Subscription::where('idtenant', $idtenant)
            ->latest()
            ->first();

        $sisahari = 0;
        $statustrial = false;
        if ($langganan && $langganan->status === 'trial' && $langganan->trial_berakhir) {
            $statustrial = true;
            $sisahari = (int) max(0, ceil(Carbon::now()->diffInDays($langganan->trial_berakhir, false)));
        }

        return view('owner.owner-dashboard', compact(
            'tenant',
            'totalbooking',
            'persenperubahanboking',
            'totalrevenue',
            'persenperubahanrevenue',
            'programaktif',
            'datarevenueperbulan',
            'labelbulan',
            'trendlayanan',
            'aktivitasterbaru',
            'statustrial',
            'sisahari',
        ));
    }
}
