<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Schedule\ExportScheduleReport;
use App\Actions\Schedule\GenerateScheduleReport;
use App\Http\Controllers\Controller;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerScheduleReportController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request, GenerateScheduleReport $generateScheduleReport): View
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $data = $generateScheduleReport->execute($tenant, $request);

        return view('owner.schedule-report', $data);
    }

    public function export(Request $request, ExportScheduleReport $exportScheduleReport): StreamedResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        return $exportScheduleReport->execute($tenant, $request);
    }
}
