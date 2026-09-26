<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Service\CreateService;
use App\Actions\Service\DeleteService;
use App\Actions\Service\ToggleServiceStatus;
use App\Actions\Service\UpdateService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\AdditionalItem;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Resource;
use App\Models\Service;
use App\Models\Staff;
use App\Traits\ResolvesOwnerTenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerProgramController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * Halaman daftar program/layanan.
     */
    public function index(Request $request): View
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $idtenant = $tenant->id;
        $katakunci = (string) $request->input('katakunci', '');
        $selectedCategory = $request->input('category', '');

        $daftarlayanan = Service::where('idtenant', $idtenant)
            ->with(['category', 'staff', 'resources', 'additionalItems'])
            ->when($katakunci !== '', function ($query) use ($katakunci) {
                $query->where('namalayanan', 'like', '%' . $katakunci . '%');
            })
            ->when($selectedCategory, function ($query) use ($selectedCategory) {
                $query->where('idcategory', $selectedCategory);
            })
            ->withCount('bookings')
            ->orderByDesc('created_at')
            ->paginate(12);

        $kategoriList = Category::where('idtenant', $idtenant)->orderBy('name')->get();
        $staffList = Staff::where('idtenant', $idtenant)->where('is_active', true)->orderBy('name')->get();
        $resourceList = Resource::where('idtenant', $idtenant)->where('is_active', true)->orderBy('name')->get();
        $additionalItemList = AdditionalItem::where('idtenant', $idtenant)->where('is_active', true)->orderBy('name')->get();

        // Statistik program dengan pembatasan tahun dan bulan berjalan
        $totallayanan = Service::where('idtenant', $idtenant)->count();
        $ratarataharga = Service::where('idtenant', $idtenant)->avg('harga') ?? 0;
        $totalbookinglayanan = Booking::where('idtenant', $idtenant)
            ->whereYear('tanggalbooking', Carbon::now()->year)
            ->whereMonth('tanggalbooking', Carbon::now()->month)
            ->count();
        $pendapatanlayanan = Booking::where('idtenant', $idtenant)
            ->whereHas('payment', fn($q) => $q->where('status', 'sukses'))
            ->whereYear('tanggalbooking', Carbon::now()->year)
            ->whereMonth('tanggalbooking', Carbon::now()->month)
            ->with('payment')
            ->get()
            ->sum(fn($b) => $b->payment->jumlah ?? 0);

        return view('owner.programs', compact(
            'tenant',
            'daftarlayanan',
            'kategoriList',
            'staffList',
            'resourceList',
            'additionalItemList',
            'totallayanan',
            'ratarataharga',
            'totalbookinglayanan',
            'pendapatanlayanan',
            'katakunci',
            'selectedCategory',
        ));
    }

    /**
     * Simpan program baru.
     */
    public function store(StoreServiceRequest $request, CreateService $createService): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $service = $createService->execute(
            $tenant,
            $request->validated(),
            $request->file('cover_image')
        );

        $redirectRoute = ($request->is('*programs*') && !str_contains($request->headers->get('referer', ''), '/services'))
            ? 'owner.programs'
            : 'owner.services';

        return redirect()->route($redirectRoute)->with('sukses', 'Program "' . $service->namalayanan . '" berhasil ditambahkan!');
    }

    /**
     * Update program.
     */
    public function update(UpdateServiceRequest $request, $program, UpdateService $updateService): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $service = $updateService->execute(
            $tenant,
            $program,
            $request->validated(),
            $request->file('cover_image'),
            $request->input('remove_image') === '1'
        );

        $redirectRoute = ($request->is('*programs*') && !str_contains($request->headers->get('referer', ''), '/services'))
            ? 'owner.programs'
            : 'owner.services';

        return redirect()->route($redirectRoute)->with('sukses', 'Program "' . $service->namalayanan . '" berhasil diperbarui!');
    }

    /**
     * Hapus program.
     */
    public function destroy($program, DeleteService $deleteService): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $namalayanan = $deleteService->execute($tenant, $program);

        $redirectRoute = (request()->is('*programs*') && !str_contains(request()->headers->get('referer', ''), '/services'))
            ? 'owner.programs'
            : 'owner.services';

        return redirect()->route($redirectRoute)->with('sukses', 'Program "' . $namalayanan . '" berhasil dihapus!');
    }

    /**
     * Toggle status aktif program.
     */
    public function toggleStatus($id, ToggleServiceStatus $toggleServiceStatus): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $service = $toggleServiceStatus->execute($tenant, $id);
        $statusText = $service->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $redirectRoute = (request()->is('*programs*') && !str_contains(request()->headers->get('referer', ''), '/services'))
            ? 'owner.programs'
            : 'owner.services';

        return redirect()->route($redirectRoute)->with('sukses', 'Program "' . $service->namalayanan . '" berhasil ' . $statusText . '!');
    }
}
