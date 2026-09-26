<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Calendar\GetOwnerCalendarData;
use App\Actions\Schedule\ExportScheduleReport;
use App\Actions\Schedule\GenerateScheduleReport;
use App\Actions\Tenant\UpdateAppearanceSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateAppearanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @deprecated Decomposed in RF-04. Delegating adapter for backward compatibility.
 * Use dedicated controllers instead:
 *  - OwnerCalendarController
 *  - OwnerScheduleReportController
 *  - OwnerAppearanceController
 *  - OwnerPaymentSettingsController
 *  - OwnerBalanceController
 *  - OwnerIntegrationController
 */
class OwnerPortalController extends Controller
{
    public function calendar(Request $request, OwnerCalendarController $controller, GetOwnerCalendarData $action): View
    {
        return $controller->index($request, $action);
    }

    public function scheduleReport(Request $request, OwnerScheduleReportController $controller, GenerateScheduleReport $action): View
    {
        return $controller->index($request, $action);
    }

    public function exportScheduleReport(Request $request, OwnerScheduleReportController $controller, ExportScheduleReport $action): StreamedResponse
    {
        return $controller->export($request, $action);
    }

    public function appearance(OwnerAppearanceController $controller): View
    {
        return $controller->index();
    }

    public function updateAppearance(UpdateAppearanceRequest $request, OwnerAppearanceController $controller, UpdateAppearanceSettings $action): RedirectResponse
    {
        return $controller->update($request, $action);
    }

    public function paymentSettings(OwnerPaymentSettingsController $controller): View
    {
        return $controller->index();
    }

    public function assets(OwnerAssetController $controller): View
    {
        return $controller->index();
    }

    public function balance(OwnerBalanceController $controller): View
    {
        return $controller->index();
    }

    public function integrations(OwnerIntegrationController $controller): View
    {
        return $controller->index();
    }
}
