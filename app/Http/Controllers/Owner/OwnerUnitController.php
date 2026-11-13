<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Unit\DeleteUnit;
use App\Actions\Unit\GetUnitsOverview;
use App\Actions\Unit\SaveUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Unit\SaveUnitRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Unit backend (BQ-152, owner-portal §8.7). Gated by the `units` module and the Pro plan
 * (AC-UNT-03); the page itself follows the redesign.
 */
class OwnerUnitController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(GetUnitsOverview $action): JsonResponse
    {
        return response()->json($action->execute($this->tenant()));
    }

    public function store(SaveUnitRequest $request, SaveUnit $action): RedirectResponse
    {
        $action->execute($this->tenant(), $request->validated());

        return back()->with('sukses', 'Unit berhasil disimpan.');
    }

    public function update(SaveUnitRequest $request, Unit $unit, SaveUnit $action): RedirectResponse
    {
        $action->execute($this->tenant(), $request->validated(), $unit);

        return back()->with('sukses', 'Unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit, DeleteUnit $action): RedirectResponse
    {
        $action->execute($this->tenant(), $unit);

        return back()->with('sukses', 'Unit berhasil dihapus.');
    }

    private function tenant(): Tenant
    {
        $tenant = $this->resolveTenant();
        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }
}
