<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Models\Tenant;
use App\Support\CsvCell;
use App\Support\ReportPeriod;
use App\Support\TenantContext;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The CSV export of one Laporan tab (BQ-147, owner-portal.md §8.13, FR-REPORT-003): the same figures
 * as the page for the same period (AC-LAP-04), every cell formula-safe (`CsvCell`), and a multi-slot
 * booking written once (AC-LAP-02). Routes refuse it below Medium (AC-LAP-03).
 */
class ExportReport
{
    public function __construct(private readonly GetReport $getReport) {}

    public function execute(Tenant $tenant, ReportPeriod $period, ?string $tab): StreamedResponse
    {
        $tab = $tab !== null && array_key_exists($tab, GetReport::TABS) ? $tab : 'ringkasan';
        $filename = 'laporan-'.$tab.'-'.$period->start->format('Ymd').'-'.$period->end->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($tenant, $period, $tab): void {
            // The stream runs after TenantMiddleware cleared the context (BQ-111); restore this tenant.
            $tenantContext = app(TenantContext::class);
            $tenantContext->setTenantId((int) $tenant->id);

            try {
                $this->write($tenant, $period, $tab);
            } finally {
                $tenantContext->clear();
            }
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function write(Tenant $tenant, ReportPeriod $period, string $tab): void
    {
        $report = $this->getReport->execute($tenant, $period, $tab);
        $handle = fopen('php://output', 'w');
        if ($handle === false || $report['summary'] === null) {
            return;
        }

        $put = function (array $cells) use ($handle): void {
            fputcsv($handle, CsvCell::row($cells), ',', '"', '');
        };

        fwrite($handle, "\xEF\xBB\xBF");
        $put(['Laporan '.GetReport::TABS[$tab]]);
        $put(['Bisnis', (string) $tenant->namabisnis]);
        $put(['Periode', $report['period']['label'], $report['period']['from'], $report['period']['to']]);
        $put(['Diekspor', now()->format('Y-m-d H:i')]);
        $put([]);

        match ($tab) {
            'pendapatan' => $this->revenue($put, $report['revenue'] ?? [], $tenant, $period),
            'okupansi' => $this->occupancy($put, $report['occupancy'] ?? []),
            'layanan' => $this->services($put, $report['services'] ?? []),
            'pelanggan' => $this->customers($put, $report['customers'] ?? [], $tenant, $period),
            default => $this->summary($put, $report['summary']),
        };

        fclose($handle);
    }

    /**
     * @param  callable(array<int, mixed>): void  $put
     * @param  array<string, mixed>  $summary
     */
    private function summary(callable $put, array $summary): void
    {
        $put(['Metrik', 'Nilai']);
        $put(['Pendapatan bersih', $summary['revenue']]);
        $put(['Booking', $summary['bookings']]);
        $put(['Pelanggan baru', $summary['newCustomers']]);
        $put(['Tingkat pembatalan (%)', $summary['cancellationRate']]);
        $put(['Tingkat selesai (%)', $summary['completionRate']]);
        if ($summary['noShowRate'] !== null) {
            $put(['Tingkat tidak datang (%)', $summary['noShowRate']]);
        }
        $put(['Rata-rata rating', $summary['averageRating'] ?? '']);
        $put(['Jumlah ulasan', $summary['reviewCount']]);
        $put(['Layanan terlaris', $summary['topService'] ?? '']);
    }

    /**
     * @param  callable(array<int, mixed>): void  $put
     * @param  array<string, mixed>  $revenue
     */
    private function revenue(callable $put, array $revenue, Tenant $tenant, ReportPeriod $period): void
    {
        $put(['Metrik', 'Nilai']);
        $put(['Pendapatan kotor', $revenue['gross']]);
        $put(['Online', $revenue['online']]);
        $put(['Walk-in', $revenue['walkIn']]);
        $put(['Refund', $revenue['refunds']]);
        $put(['Pendapatan bersih', $revenue['net']]);
        $put([]);

        $put([$revenue['daily'] ? 'Hari' : 'Bulan', 'Kotor', 'Refund', 'Bersih']);
        foreach ($revenue['series'] as $point) {
            $put([$point['label'], $point['gross'], $point['refunds'], $point['net']]);
        }
        $put([]);

        $put(['Layanan', 'Kotor', 'Refund', 'Bersih']);
        foreach ($revenue['perService'] as $row) {
            $put([$row['name'], $row['gross'], $row['refunds'], $row['net']]);
        }
        $put([]);

        $put(['Tanggal', 'Kode booking', 'Pelanggan', 'Layanan', 'Jumlah', 'Kanal', 'Jumlah slot']);
        foreach ($this->getReport->paymentRows($tenant, $period) as $row) {
            $put([$row['date'], $row['code'], $row['customer'], $row['service'], $row['amount'], $row['walkIn'] ? 'Walk-in' : 'Online', $row['slots']]);
        }
    }

    /**
     * @param  callable(array<int, mixed>): void  $put
     * @param  array<string, mixed>  $occupancy
     */
    private function occupancy(callable $put, array $occupancy): void
    {
        $put(['Metrik', 'Nilai']);
        $put(['Tingkat okupansi (%)', $occupancy['rate']]);
        $put(['Total slot', $occupancy['total']]);
        $put(['Slot terisi', $occupancy['booked']]);
        $put(['Slot kosong', $occupancy['free']]);
        $put([]);

        $hours = $occupancy['heatmap']['hours'];
        $put(['Hari', ...array_map(fn (int $hour): string => sprintf('%02d.00', $hour), $hours)]);
        foreach ($occupancy['heatmap']['days'] as $day => $label) {
            $put([$label, ...array_values($occupancy['heatmap']['cells'][$day])]);
        }
    }

    /**
     * @param  callable(array<int, mixed>): void  $put
     * @param  list<array{name: string, bookings: int, revenue: float}>  $services
     */
    private function services(callable $put, array $services): void
    {
        $put(['Layanan', 'Booking', 'Pendapatan bersih']);
        foreach ($services as $row) {
            $put([$row['name'], $row['bookings'], $row['revenue']]);
        }
    }

    /**
     * @param  callable(array<int, mixed>): void  $put
     * @param  array<string, mixed>  $customers
     */
    private function customers(callable $put, array $customers, Tenant $tenant, ReportPeriod $period): void
    {
        $put(['Metrik', 'Nilai']);
        $put(['Pelanggan aktif', $customers['active']]);
        $put(['Pelanggan baru', $customers['new']]);
        $put(['Pelanggan kembali', $customers['returning']]);
        $put([]);

        $put(['Pelanggan', 'Booking', 'Jenis']);
        foreach ($this->getReport->customerRows($tenant, $period) as $row) {
            $put([$row['name'], $row['bookings'], $row['returning'] ? 'Kembali' : 'Baru']);
        }
    }
}
