<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Report\ExportReport;
use App\Actions\Report\GetReport;
use App\Http\Controllers\Controller;
use App\Support\ReportPeriod;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan (BQ-147, owner-portal.md §8.13): Pemilik and Manajer through `reports.view`. Small sees a
 * preview with the upgrade prompt; the export route is refused below Medium (AC-LAP-03).
 */
class OwnerAnalyticsController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request, GetReport $getReport): View
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

        return view('owner.analytics', [
            'tenant' => $tenant,
            'report' => $getReport->execute($tenant, ReportPeriod::fromQuery($request->query()), $this->tab($request)),
        ]);
    }

    public function export(Request $request, ExportReport $exportReport): StreamedResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

        return $exportReport->execute($tenant, ReportPeriod::fromQuery($request->query()), $this->tab($request));
    }

    /**
     * The former schedule report is the Okupansi tab (§10.2).
     */
    public function legacyScheduleReport(Request $request): RedirectResponse
    {
        return redirect()->route('owner.analytics', ['tab' => 'okupansi'] + $request->query(), 301);
    }

    public function legacyScheduleReportExport(Request $request): RedirectResponse
    {
        return redirect()->route('owner.analytics.export', ['tab' => 'okupansi'] + $request->query(), 301);
    }

    private function tab(Request $request): ?string
    {
        return is_string($request->query('tab')) ? $request->query('tab') : null;
    }
}
