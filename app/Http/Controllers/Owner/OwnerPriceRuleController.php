<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Schedule\ApplyBasePriceToOpenSlots;
use App\Actions\Schedule\BulkCreateSchedules;
use App\Actions\Schedule\GetSlotRunway;
use App\Actions\Schedule\SaveOpeningHours;
use App\Actions\Schedule\SavePriceRules;
use App\Actions\Schedule\SimulateSlotPrices;
use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\BulkCreateScheduleRequest;
use App\Http\Requests\Schedule\SaveOpeningHoursRequest;
use App\Http\Requests\Schedule\SavePeakHourRuleRequest;
use App\Http\Requests\Schedule\SaveSpecialDatePriceRequest;
use App\Http\Requests\Schedule\SimulateSlotPricesRequest;
use App\Models\PeakHourRule;
use App\Models\Service;
use App\Models\SpecialDatePrice;
use App\Models\Tenant;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Jam Buka & Harga backend (BQ-133, owner-portal §8.8): opening hours, peak-hour rules,
 * special-date prices, the price simulator, "Terapkan harga dasar", the bulk preview and the
 * slot runway. The page itself follows BQ-129.
 */
class OwnerPriceRuleController extends Controller
{
    use ResolvesOwnerTenant;

    public function saveOpeningHours(SaveOpeningHoursRequest $request, SaveOpeningHours $action): RedirectResponse
    {
        $slotOpenDaysAhead = $request->validated('slot_open_days_ahead');
        $action->execute($this->tenant(), $request->days(), $slotOpenDaysAhead !== null ? (int) $slotOpenDaysAhead : null);

        return back()->with('sukses', 'Jam buka berhasil disimpan.');
    }

    public function storePeakRule(SavePeakHourRuleRequest $request, SavePriceRules $action): RedirectResponse
    {
        $action->savePeakRule($this->tenant(), $request->validated());

        return back()->with('sukses', 'Aturan jam sibuk berhasil disimpan.');
    }

    public function updatePeakRule(SavePeakHourRuleRequest $request, PeakHourRule $peakRule, SavePriceRules $action): RedirectResponse
    {
        $action->savePeakRule($this->tenant(), $request->validated(), $peakRule);

        return back()->with('sukses', 'Aturan jam sibuk berhasil diperbarui.');
    }

    public function destroyPeakRule(PeakHourRule $peakRule, SavePriceRules $action): RedirectResponse
    {
        $action->deletePeakRule($this->tenant(), $peakRule);

        return back()->with('sukses', 'Aturan jam sibuk berhasil dihapus.');
    }

    public function storeSpecialDate(SaveSpecialDatePriceRequest $request, SavePriceRules $action): RedirectResponse
    {
        $data = $request->validated();
        $action->saveSpecialDate($this->tenant(), (string) $data['tanggal'], (float) $data['price'], array_map('intval', $data['service_ids']));

        return back()->with('sukses', 'Harga tanggal khusus berhasil disimpan.');
    }

    public function destroySpecialDate(SpecialDatePrice $specialDate, SavePriceRules $action): RedirectResponse
    {
        $action->deleteSpecialDate($this->tenant(), $specialDate);

        return back()->with('sukses', 'Harga tanggal khusus berhasil dihapus.');
    }

    public function simulate(SimulateSlotPricesRequest $request, SimulateSlotPrices $action): JsonResponse
    {
        $tenant = $this->tenant();

        return response()->json([
            'hours' => $action->execute($tenant, $this->service($tenant, (int) $request->validated('idlayanan')), (string) $request->validated('tanggal')),
        ]);
    }

    public function applyBasePrice(Request $request, ApplyBasePriceToOpenSlots $action): RedirectResponse
    {
        $data = $request->validate(['idlayanan' => ['required', 'integer']]);
        $tenant = $this->tenant();
        $count = $action->execute($tenant, $this->service($tenant, (int) $data['idlayanan']));

        return back()->with('sukses', $count.' slot terbuka kini memakai harga dasar.');
    }

    public function bulkPreview(BulkCreateScheduleRequest $request, BulkCreateSchedules $action): JsonResponse
    {
        $result = $action->preview($this->tenant(), $request->validated());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function runway(GetSlotRunway $action): JsonResponse
    {
        return response()->json(['services' => $action->execute($this->tenant())]);
    }

    private function tenant(): Tenant
    {
        return $this->resolveTenant() ?? abort(404);
    }

    private function service(Tenant $tenant, int $serviceId): Service
    {
        // scope-bypass: filtered by the authenticated tenant
        return Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->findOrFail($serviceId);
    }
}
