<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Tenant\UpdateAppearanceSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateAppearanceRequest;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OwnerAppearanceController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(): View
    {
        $tenant = $this->resolveTenant();
        return view('owner.appearance', compact('tenant'));
    }

    public function update(UpdateAppearanceRequest $request, UpdateAppearanceSettings $updateAppearanceSettings): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $updateAppearanceSettings->execute($tenant, $request->validated());

        return redirect()->route('owner.settings.appearance')
            ->with('sukses', 'Pengaturan tema & tampilan publik berhasil disimpan!');
    }
}
