<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OwnerDashboardController extends Controller
{
    use \App\Traits\ResolvesOwnerTenant;

    public function index()
    {
        $tenant = $this->resolveTenant();
        $user = auth()->user();

        if (!$user) {
            abort(403, 'User tidak ditemukan.');
        }

        $showProfilePrompt = false;
        $showPaymentPrompt = false;

        if (!$tenant) {
            $tenant = new Tenant();
            $tenant->setRelation('user', $user);
            $showProfilePrompt = true;

            $labelbulan = [];
            $datarevenueperbulan = [];
            for ($i = 6; $i >= 0; $i--) {
                $labelbulan[] = Carbon::now()->subMonths($i)->startOfMonth()->format('M');
                $datarevenueperbulan[] = 0;
            }

            $labelminggu = [];
            $datarevenueperminggu = [];
            for ($i = 6; $i >= 0; $i--) {
                $labelminggu[] = Carbon::today()->subDays($i)->format('d M');
                $datarevenueperminggu[] = 0;
            }

            return view('owner.dashboard', [
                'tenant' => $tenant,
                'totalbooking' => 0,
                'persenperubahanboking' => 0,
                'totalrevenue' => 0,
                'persenperubahanrevenue' => 0,
                'programaktif' => 0,
                'totalpelanggan' => 0,
                'datarevenueperbulan' => $datarevenueperbulan,
                'labelbulan' => $labelbulan,
                'datarevenueperminggu' => $datarevenueperminggu,
                'labelminggu' => $labelminggu,
                'trendlayanan' => collect(),
                'aktivitasterbaru' => collect(),
                'upcomingbookings' => collect(),
                'statustrial' => false,
                'sisahari' => 0,
                'showProfilePrompt' => $showProfilePrompt,
                'showPaymentPrompt' => $showPaymentPrompt,
            ]);
        }

        $idtenant = $tenant->id;
        $bulanini = Carbon::now()->startOfMonth();
        $akhirbulanini = Carbon::now()->endOfMonth();
        $bulanlalu = Carbon::now()->subMonth()->startOfMonth();
        $akhirbulanlalu = Carbon::now()->subMonth()->endOfMonth();

        // ── Total Booking ──
        $totalbooking = Booking::where('idtenant', $idtenant)->count();

        $bookinbulanini = Booking::where('idtenant', $idtenant)
            ->whereBetween('tanggalbooking', [$bulanini, $akhirbulanini])
            ->count();

        $bookinbulanlalu = Booking::where('idtenant', $idtenant)
            ->whereBetween('tanggalbooking', [$bulanlalu, $akhirbulanlalu])
            ->count();

        $persenperubahanboking = $bookinbulanlalu > 0
            ? round((($bookinbulanini - $bookinbulanlalu) / $bookinbulanlalu) * 100)
            : ($bookinbulanini > 0 ? 100 : 0);

        // ── Total Revenue ──
        $totalrevenue = Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->sum('jumlah');

        $revenuebulanini = Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->whereBetween('created_at', [$bulanini, $akhirbulanini])
            ->sum('jumlah');

        $revenuebulanlalu = Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->whereBetween('created_at', [$bulanlalu, $akhirbulanlalu])
            ->sum('jumlah');

        $persenperubahanrevenue = $revenuebulanlalu > 0
            ? round((($revenuebulanini - $revenuebulanlalu) / $revenuebulanlalu) * 100)
            : ($revenuebulanini > 0 ? 100 : 0);

        // ── Active Programs ──
        $programaktif = Service::where('idtenant', $idtenant)
            ->where('is_active', true)
            ->count();

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

        // ── Revenue Mingguan (7 hari terakhir) ──
        $datarevenueperminggu = [];
        $labelminggu = [];
        for ($i = 6; $i >= 0; $i--) {
            $targetHari = Carbon::today()->subDays($i);
            $labelminggu[] = $targetHari->translatedFormat('D, d M');

            $revHari = Payment::where('idtenant', $idtenant)
                ->where('tipe', 'booking')
                ->where('status', 'sukses')
                ->whereDate('created_at', $targetHari)
                ->sum('jumlah');

            $datarevenueperminggu[] = round($revHari);
        }

        // ── Daily Trends (layanan terpopuler hari ini vs kemarin) ──
        $hariini = Carbon::today();
        $kemarin = Carbon::yesterday();

        $trendlayanan = Service::where('services.idtenant', $idtenant)
            ->where('services.is_active', true)
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
                if ($layanan->bookingskemarin > 0) {
                    $perubahan = round((($layanan->bookingshariini - $layanan->bookingskemarin) / $layanan->bookingskemarin) * 100);
                } elseif ($layanan->bookingshariini > 0) {
                    $perubahan = 100;
                } else {
                    $perubahan = 0;
                }

                $trennya = 'stabil';
                if ($layanan->bookingshariini > $layanan->bookingskemarin) {
                    $trennya = 'naik';
                } elseif ($layanan->bookingshariini < $layanan->bookingskemarin) {
                    $trennya = 'turun';
                }

                return [
                    'namalayanan' => $layanan->namalayanan,
                    'jumlahbooking' => $layanan->bookingshariini,
                    'persenperubahan' => $perubahan,
                    'trennya' => $trennya,
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

        $totalpelanggan = DB::table('bookings')
            ->where('idtenant', $idtenant)
            ->distinct()
            ->count(DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id))))"));

        $upcomingbookings = Booking::where('bookings.idtenant', $idtenant)
            ->where('tanggalbooking', '>=', Carbon::today())
            ->whereIn('status', ['paid', 'pending'])
            ->with('layanan')
            ->orderBy('tanggalbooking')
            ->orderBy('jam')
            ->limit(5)
            ->get();

        $showProfilePrompt = !$tenant->namabisnis || !$tenant->slug || !$tenant->jenisbisnis || !$tenant->nomorhp;
        $showPaymentPrompt = ($tenant->payment_mode ?? 'platform') === 'owner'
            && ($tenant->midtrans_status ?? 'pending') !== 'approved';

        return view('owner.dashboard', compact(
            'tenant',
            'totalbooking',
            'persenperubahanboking',
            'totalrevenue',
            'persenperubahanrevenue',
            'programaktif',
            'totalpelanggan',
            'datarevenueperbulan',
            'labelbulan',
            'datarevenueperminggu',
            'labelminggu',
            'trendlayanan',
            'aktivitasterbaru',
            'upcomingbookings',
            'statustrial',
            'sisahari',
            'showProfilePrompt',
            'showPaymentPrompt',
        ));
    }

    public function pollingData()
    {
        $tenant = $this->resolveTenant();

        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant tidak ditemukan.'], 404);
        }

        $idtenant = $tenant->id;

        $compute = function () use ($idtenant) {
            $bulanini = Carbon::now()->startOfMonth();
            $akhirbulanini = Carbon::now()->endOfMonth();
            $bulanlalu = Carbon::now()->subMonth()->startOfMonth();
            $akhirbulanlalu = Carbon::now()->subMonth()->endOfMonth();

            // ── Total Booking ──
            $totalbooking = Booking::where('idtenant', $idtenant)->count();

            $bookinbulanini = Booking::where('idtenant', $idtenant)
                ->whereBetween('tanggalbooking', [$bulanini, $akhirbulanini])
                ->count();

            $bookinbulanlalu = Booking::where('idtenant', $idtenant)
                ->whereBetween('tanggalbooking', [$bulanlalu, $akhirbulanlalu])
                ->count();

            $persenperubahanboking = $bookinbulanlalu > 0
                ? round((($bookinbulanini - $bookinbulanlalu) / $bookinbulanlalu) * 100)
                : ($bookinbulanini > 0 ? 100 : 0);

            // ── Total Revenue ──
            $totalrevenue = Payment::where('idtenant', $idtenant)
                ->where('tipe', 'booking')
                ->where('status', 'sukses')
                ->sum('jumlah');

            $revenuebulanini = Payment::where('idtenant', $idtenant)
                ->where('tipe', 'booking')
                ->where('status', 'sukses')
                ->whereBetween('created_at', [$bulanini, $akhirbulanini])
                ->sum('jumlah');

            $revenuebulanlalu = Payment::where('idtenant', $idtenant)
                ->where('tipe', 'booking')
                ->where('status', 'sukses')
                ->whereBetween('created_at', [$bulanlalu, $akhirbulanlalu])
                ->sum('jumlah');

            $persenperubahanrevenue = $revenuebulanlalu > 0
                ? round((($revenuebulanini - $revenuebulanlalu) / $revenuebulanlalu) * 100)
                : ($revenuebulanini > 0 ? 100 : 0);

            // ── Active Programs & Customers ──
            $programaktif = Service::where('idtenant', $idtenant)
                ->where('is_active', true)
                ->count();

            $totalpelanggan = DB::table('bookings')
                ->where('idtenant', $idtenant)
                ->distinct()
                ->count(DB::raw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id))))"));

            // ── Recent Activity ──
            $aktivitasterbaru = Booking::where('bookings.idtenant', $idtenant)
                ->with('layanan')
                ->orderByDesc('bookings.created_at')
                ->limit(10)
                ->get()
                ->map(function ($aktivitas) {
                    $tanggalFormatted = $aktivitas->tanggalbooking instanceof Carbon
                        ? $aktivitas->tanggalbooking->format('d M Y')
                        : Carbon::parse($aktivitas->tanggalbooking)->format('d M Y');

                    return [
                        'id' => $aktivitas->id,
                        'program_name' => $aktivitas->layanan->namalayanan ?? '-',
                        'customer_name' => $aktivitas->namapelanggan,
                        'date' => $tanggalFormatted,
                        'status' => $aktivitas->status,
                    ];
                });

            return [
                'total_bookings' => $totalbooking,
                'persen_perubahan_booking' => $persenperubahanboking,
                'total_revenue' => $totalrevenue,
                'persen_perubahan_revenue' => $persenperubahanrevenue,
                'total_customers' => $totalpelanggan,
                'active_services' => $programaktif,
                'recent_activities' => $aktivitasterbaru,
            ];
        };

        if (app()->runningUnitTests()) {
            $data = $compute();
        } else {
            $data = Cache::remember("tenant:{$idtenant}:dashboard:polling", 15, $compute);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}

