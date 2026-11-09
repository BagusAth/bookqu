<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Service\CreateService;
use App\Actions\Service\DeleteService;
use App\Actions\Service\GetLayananPage;
use App\Actions\Service\ToggleServiceStatus;
use App\Actions\Service\UpdateService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerProgramController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * The Layanan page (owner-portal.md §8.6): tab Layanan by default, tab Kategori with `?tab=kategori`.
     */
    public function index(Request $request, GetLayananPage $getLayananPage): View
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $tab = $request->query('tab') === GetLayananPage::TAB_CATEGORIES ? GetLayananPage::TAB_CATEGORIES : GetLayananPage::TAB_SERVICES;

        return view('owner.services', array_merge(
            $getLayananPage->execute($tenant, $request->only(['katakunci', 'category', 'search', 'status', 'sort'])),
            ['tenant' => $tenant, 'activeTab' => $tab],
        ));
    }

    /**
     * The former programs page moved to Layanan (owner-portal.md §8.6: `owner.programs.*` redirects to `owner.services.*`).
     */
    public function legacyIndex(Request $request): RedirectResponse
    {
        return redirect()->route('owner.services', $request->query(), 301);
    }

    /**
     * Simpan program baru.
     */
    public function store(StoreServiceRequest $request, CreateService $createService): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $service = $createService->execute(
            $tenant,
            $request->validated(),
            $request->file('cover_image')
        );

        $redirectRoute = ($request->is('*programs*') && ! str_contains($request->headers->get('referer', ''), '/services'))
            ? 'owner.programs'
            : 'owner.services';

        return redirect()->route($redirectRoute)->with('sukses', 'Layanan "'.$service->namalayanan.'" berhasil ditambahkan!');
    }

    /**
     * Update program.
     */
    public function update(UpdateServiceRequest $request, $program, UpdateService $updateService): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $service = $updateService->execute(
            $tenant,
            $program,
            $request->validated(),
            $request->file('cover_image'),
            $request->input('remove_image') === '1'
        );

        $redirectRoute = ($request->is('*programs*') && ! str_contains($request->headers->get('referer', ''), '/services'))
            ? 'owner.programs'
            : 'owner.services';

        return redirect()->route($redirectRoute)->with('sukses', 'Layanan "'.$service->namalayanan.'" berhasil diperbarui!');
    }

    /**
     * Hapus program.
     */
    public function destroy($program, DeleteService $deleteService): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $namalayanan = $deleteService->execute($tenant, $program);

        $redirectRoute = (request()->is('*programs*') && ! str_contains(request()->headers->get('referer', ''), '/services'))
            ? 'owner.programs'
            : 'owner.services';

        return redirect()->route($redirectRoute)->with('sukses', 'Layanan "'.$namalayanan.'" berhasil dihapus!');
    }

    /**
     * Toggle status aktif program.
     */
    public function toggleStatus($id, ToggleServiceStatus $toggleServiceStatus): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $service = $toggleServiceStatus->execute($tenant, $id);
        $statusText = $service->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $redirectRoute = (request()->is('*programs*') && ! str_contains(request()->headers->get('referer', ''), '/services'))
            ? 'owner.programs'
            : 'owner.services';

        return redirect()->route($redirectRoute)->with('sukses', 'Layanan "'.$service->namalayanan.'" berhasil '.$statusText.'!');
    }
}
