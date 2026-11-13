<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Dashboard\GetOwnerAnalyticsTrends;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\CsvCell;
use App\Support\TenantContext;
use App\Traits\ResolvesOwnerTenant;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerAnalyticsController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * Halaman analytics.
     */
    public function index()
    {
        $tenant = $this->resolveTenant();
        $user = auth()->user();
        if (! $tenant) {
            $tenant = new Tenant;
            if ($user) {
                $tenant->setRelation('user', $user);
            }

            $labelbulanpanjang = [];
            $revenueperbulan = [];
            $bookingperbulan = [];
            for ($i = 11; $i >= 0; $i--) {
                $labelbulanpanjang[] = Carbon::now()->subMonths($i)->startOfMonth()->format('M Y');
                $revenueperbulan[] = 0;
                $bookingperbulan[] = 0;
            }

            return view('owner.analytics', [
                'tenant' => $tenant,
                'revenueperbulan' => $revenueperbulan,
                'bookingperbulan' => $bookingperbulan,
                'labelbulanpanjang' => $labelbulanpanjang,
                'revenuebulanini' => 0,
                'persenperubahanrevenue' => 0,
                'totalrevenuesetahun' => 0,
                'bookingbulanini' => 0,
                'persenbooking' => 0,
                'toplayanan' => collect(),
                'distribusistatus' => ['completed' => 0, 'paid' => 0, 'pending' => 0, 'cancelled' => 0, 'no_show' => 0],
                'revenueperLayanan' => collect(),
                'bookingperhari' => array_fill(0, 30, 0),
                'labelhari' => array_map(fn ($i) => Carbon::now()->subDays(29 - $i)->format('d M'), range(0, 29)),
                'ratarataharian' => 0,
                'tingkatkonversi' => 0,
            ]);
        }

        $idtenant = $tenant->id;

        // ── Revenue 12 bulan terakhir & booking 30 hari terakhir (grouped queries, BQ-111) ──
        [
            'labelbulanpanjang' => $labelbulanpanjang,
            'revenueperbulan' => $revenueperbulan,
            'bookingperbulan' => $bookingperbulan,
            'labelhari' => $labelhari,
            'bookingperhari' => $bookingperhari,
        ] = app(GetOwnerAnalyticsTrends::class)->execute((int) $idtenant);

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
            'no_show' => Booking::where('idtenant', $idtenant)->where('status', 'no_show')->count(),
        ];

        // ── Revenue per layanan ──
        $revenueperLayanan = Service::where('services.idtenant', $idtenant)
            ->select('services.id', 'services.namalayanan', 'services.harga')
            ->withCount(['bookings as bookingbayar' => function ($q) {
                $q->whereIn('status', ['paid', 'completed', 'no_show']);
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
        $ratarataharian = count($bookingperhari) > 0 ? round(array_sum($bookingperhari) / count($bookingperhari), 1) : 0;

        // ── Conversion rate ──
        $totalsemuabooking = Booking::where('idtenant', $idtenant)->count();
        $totalbayar = Booking::where('idtenant', $idtenant)->whereIn('status', ['paid', 'completed', 'no_show'])->count();
        $tingkatkonversi = $totalsemuabooking > 0 ? round(($totalbayar / $totalsemuabooking) * 100, 1) : 0;

        return view('owner.analytics', compact(
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

    public function export(): StreamedResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $filename = 'analytics-'.$tenant->slug.'-'.now()->format('Ymd-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () use ($tenant) {
            // The stream runs after TenantMiddleware cleared the context, so the tenant scope
            // would match nothing (BQ-111); restore the already-resolved tenant for its queries.
            $tenantContext = app(TenantContext::class);
            $tenantContext->setTenantId((int) $tenant->id);

            try {
                $this->writeExportRows($tenant);
            } finally {
                $tenantContext->clear();
            }
        }, $filename, $headers);
    }

    private function writeExportRows(Tenant $tenant): void
    {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, [
            'Tanggal',
            'Nama Program',
            'Nama Pelanggan',
            'Email',
            'Nomor HP',
            'Status',
            'Harga',
            'Keterangan Harga',
        ]);

        // BQ-111: a transaction's amount is written once, on its first row; there is no
        // historical per-session amount to split it into (BQ-121).
        $writtenPayments = [];

        Booking::where('idtenant', $tenant->id)
            ->with(['layanan', 'payment' => fn ($query) => $query->withCount('bookings')])
            ->orderByDesc('tanggalbooking')
            ->orderBy('jam')
            ->orderBy('id')
            ->chunk(500, function ($bookings) use ($handle, &$writtenPayments) {
                foreach ($bookings as $booking) {
                    $harga = $booking->payment?->jumlah ?? $booking->layanan->harga ?? 0;
                    $keterangan = '';

                    $sessions = (int) ($booking->payment->bookings_count ?? 0);
                    if ($booking->payment !== null && $sessions > 1) {
                        if (isset($writtenPayments[(int) $booking->idpayment])) {
                            $harga = '';
                            $keterangan = 'Termasuk dalam total transaksi';
                        } else {
                            $writtenPayments[(int) $booking->idpayment] = true;
                            $keterangan = 'Total transaksi '.$sessions.' sesi';
                        }
                    }

                    fputcsv($handle, CsvCell::row([
                        optional($booking->tanggalbooking)->format('Y-m-d'),
                        $booking->layanan->namalayanan ?? '-',
                        $booking->namapelanggan,
                        $booking->email,
                        $booking->nomorhp,
                        $booking->status,
                        $harga,
                        $keterangan,
                    ]));
                }
            });

        fclose($handle);
    }
}
