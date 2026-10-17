<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetOwnerDashboardOverview
{
    /**
     * Compute full dashboard overview metrics, charts, and activity data.
     *
     * @return array<string, mixed>
     */
    public function getOverviewData(Tenant $tenant): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

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
            ? (int) round((($bookinbulanini - $bookinbulanlalu) / $bookinbulanlalu) * 100)
            : ($bookinbulanini > 0 ? 100 : 0);

        // ── Total Revenue ──
        $totalrevenue = (float) Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->sum('jumlah');

        $revenuebulanini = (float) Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->whereBetween('created_at', [$bulanini, $akhirbulanini])
            ->sum('jumlah');

        $revenuebulanlalu = (float) Payment::where('idtenant', $idtenant)
            ->where('tipe', 'booking')
            ->where('status', 'sukses')
            ->whereBetween('created_at', [$bulanlalu, $akhirbulanlalu])
            ->sum('jumlah');

        $persenperubahanrevenue = $revenuebulanlalu > 0
            ? (int) round((($revenuebulanini - $revenuebulanlalu) / $revenuebulanlalu) * 100)
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

            $datarevenueperbulan[] = round((float) $revenuenya);
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

            $datarevenueperminggu[] = round((float) $revHari);
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

        // ── Subscription / Trial via SubscriptionState ──
        $langganan = Subscription::where('idtenant', $idtenant)
            ->latest()
            ->first();

        $sisahari = \App\Domain\Subscription\SubscriptionState::getRemainingTrialDays($langganan);
        $statustrial = ($langganan !== null
            && $langganan->status === \App\Domain\Subscription\SubscriptionState::STATUS_TRIAL
            && !\App\Domain\Subscription\SubscriptionState::isExpired($langganan)
            && $langganan->trial_berakhir !== null);

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

        return [
            'tenant' => $tenant,
            'totalbooking' => $totalbooking,
            'persenperubahanboking' => $persenperubahanboking,
            'totalrevenue' => $totalrevenue,
            'persenperubahanrevenue' => $persenperubahanrevenue,
            'programaktif' => $programaktif,
            'totalpelanggan' => $totalpelanggan,
            'datarevenueperbulan' => $datarevenueperbulan,
            'labelbulan' => $labelbulan,
            'datarevenueperminggu' => $datarevenueperminggu,
            'labelminggu' => $labelminggu,
            'trendlayanan' => $trendlayanan,
            'aktivitasterbaru' => $aktivitasterbaru,
            'upcomingbookings' => $upcomingbookings,
            'statustrial' => $statustrial,
            'sisahari' => $sisahari,
            'showProfilePrompt' => $showProfilePrompt,
            'showPaymentPrompt' => $showPaymentPrompt,
        ];
    }

    /**
     * Compute lightweight dashboard polling metrics.
     *
     * @return array<string, mixed>
     */
    public function getPollingData(Tenant $tenant): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

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
                ? (int) round((($bookinbulanini - $bookinbulanlalu) / $bookinbulanlalu) * 100)
                : ($bookinbulanini > 0 ? 100 : 0);

            // ── Total Revenue ──
            $totalrevenue = (float) Payment::where('idtenant', $idtenant)
                ->where('tipe', 'booking')
                ->where('status', 'sukses')
                ->sum('jumlah');

            $revenuebulanini = (float) Payment::where('idtenant', $idtenant)
                ->where('tipe', 'booking')
                ->where('status', 'sukses')
                ->whereBetween('created_at', [$bulanini, $akhirbulanini])
                ->sum('jumlah');

            $revenuebulanlalu = (float) Payment::where('idtenant', $idtenant)
                ->where('tipe', 'booking')
                ->where('status', 'sukses')
                ->whereBetween('created_at', [$bulanlalu, $akhirbulanlalu])
                ->sum('jumlah');

            $persenperubahanrevenue = $revenuebulanlalu > 0
                ? (int) round((($revenuebulanini - $revenuebulanlalu) / $revenuebulanlalu) * 100)
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
            return $compute();
        }

        return Cache::remember("tenant:{$idtenant}:dashboard:polling", 15, $compute);
    }
}
