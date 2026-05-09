<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerAnalyticsController extends Controller
{
    /**
     * Halaman analytics.
     */
    public function index()
    {
        $iduser = Auth::id();
        $tenant = Tenant::with('user')->where('iduser', $iduser)->first();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $idtenant = $tenant->id;

        // ── Revenue 12 bulan terakhir ──
        $revenueperbulan = [];
        $bookingperbulan = [];
        $labelbulanpanjang = [];
        for ($i = 11; $i >= 0; $i--) {
            $awal = Carbon::now()->subMonths($i)->startOfMonth();
            $akhir = Carbon::now()->subMonths($i)->endOfMonth();
            $labelbulanpanjang[] = $awal->format('M Y');

            $revenueperbulan[] = round(Payment::where('idtenant', $idtenant)
                ->where('tipe', 'booking')->where('status', 'sukses')
                ->whereBetween('created_at', [$awal, $akhir])
                ->sum('jumlah'));

            $bookingperbulan[] = Booking::where('idtenant', $idtenant)
                ->whereBetween('tanggalbooking', [$awal->format('Y-m-d'), $akhir->format('Y-m-d')])
                ->count();
        }

        // ── Revenue summary ──
        $revenuebulanini = end($revenueperbulan);
        $revenuebulanlalu = $revenueperbulan[count($revenueperbulan) - 2] ?? 0;
        $persenperubahanrevenue = $revenuebulanlalu > 0
            ? round((($revenuebulanini - $revenuebulanlalu) / $revenuebulanlalu) * 100)
            : 0;
        $totalrevenuesetahun = array_sum($revenueperbulan);

        // ── Booking summary ──
        $bookingbulanini = end($bookingperbulan);
        $bookingbulanlalu = $bookingperbulan[count($bookingperbulan) - 2] ?? 0;
        $persenbooking = $bookingbulanlalu > 0
            ? round((($bookingbulanini - $bookingbulanlalu) / $bookingbulanlalu) * 100)
            : 0;

        // ── Top layanan ──
        $toplayanan = Service::where('services.idtenant', $idtenant)
            ->withCount('bookings')
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->get();

        // ── Status distribution ──
        $distribusistatus = [
            'completed' => Booking::where('idtenant', $idtenant)->where('status', 'completed')->count(),
            'paid' => Booking::where('idtenant', $idtenant)->where('status', 'paid')->count(),
            'pending' => Booking::where('idtenant', $idtenant)->where('status', 'pending')->count(),
            'cancelled' => Booking::where('idtenant', $idtenant)->where('status', 'cancelled')->count(),
        ];

        // ── Revenue per layanan ──
        $revenueperLayanan = Service::where('services.idtenant', $idtenant)
            ->select('services.id', 'services.namalayanan', 'services.harga')
            ->withCount(['bookings as bookingbayar' => function ($q) {
                $q->whereIn('status', ['paid', 'completed']);
            }])
            ->orderByDesc('bookingbayar')
            ->limit(6)
            ->get()
            ->map(function ($layanan) {
                return [
                    'namalayanan' => $layanan->namalayanan,
                    'revenue' => round($layanan->harga * $layanan->bookingbayar),
                    'jumlahbooking' => $layanan->bookingbayar,
                ];
            });

        // ── Rata-rata per hari (30 hari terakhir) ──
        $bookingperhari = [];
        $labelhari = [];
        for ($i = 29; $i >= 0; $i--) {
            $tanggalnya = Carbon::now()->subDays($i);
            $labelhari[] = $tanggalnya->format('d M');
            $bookingperhari[] = Booking::where('idtenant', $idtenant)
                ->whereDate('tanggalbooking', $tanggalnya->format('Y-m-d'))
                ->count();
        }
        $ratarataharian = count($bookingperhari) > 0 ? round(array_sum($bookingperhari) / count($bookingperhari), 1) : 0;

        // ── Conversion rate ──
        $totalsemuabooking = Booking::where('idtenant', $idtenant)->count();
        $totalbayar = Booking::where('idtenant', $idtenant)->whereIn('status', ['paid', 'completed'])->count();
        $tingkatkonversi = $totalsemuabooking > 0 ? round(($totalbayar / $totalsemuabooking) * 100, 1) : 0;

        return view('owner.owner-analytics', compact(
            'tenant',
            'revenueperbulan',
            'bookingperbulan',
            'labelbulanpanjang',
            'revenuebulanini',
            'persenperubahanrevenue',
            'totalrevenuesetahun',
            'bookingbulanini',
            'persenbooking',
            'toplayanan',
            'distribusistatus',
            'revenueperLayanan',
            'bookingperhari',
            'labelhari',
            'ratarataharian',
            'tingkatkonversi',
        ));
    }
}
