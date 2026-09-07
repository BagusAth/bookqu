<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\UsageLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Traits\ClearsBookingCache;

class OwnerProgramController extends Controller
{
    use ClearsBookingCache;
    use \App\Traits\ResolvesOwnerTenant;

    /**
     * Halaman daftar program/layanan.
     */
    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $idtenant = $tenant->id;
        $katakunci = $request->input('katakunci', '');

        $daftarlayanan = Service::where('idtenant', $idtenant)
            ->with(['category', 'staff', 'resources', 'additionalItems'])
            ->when($katakunci, function ($query) use ($katakunci) {
                $query->where('namalayanan', 'like', '%' . $katakunci . '%');
            })
            ->withCount('bookings')
            ->orderByDesc('created_at')
            ->paginate(12);

        $kategoriList = \App\Models\Category::where('idtenant', $idtenant)->orderBy('name')->get();
        $staffList = \App\Models\Staff::where('idtenant', $idtenant)->where('is_active', true)->orderBy('name')->get();
        $resourceList = \App\Models\Resource::where('idtenant', $idtenant)->where('is_active', true)->orderBy('name')->get();
        $additionalItemList = \App\Models\AdditionalItem::where('idtenant', $idtenant)->where('is_active', true)->orderBy('name')->get();

        // Statistik program
        $totallayanan = Service::where('idtenant', $idtenant)->count();
        $ratarataharga = Service::where('idtenant', $idtenant)->avg('harga') ?? 0;
        $totalbookinglayanan = Booking::where('idtenant', $idtenant)
            ->whereMonth('tanggalbooking', Carbon::now()->month)
            ->count();
        $pendapatanlayanan = Booking::where('idtenant', $idtenant)
            ->whereHas('payment', fn($q) => $q->where('status', 'sukses'))
            ->whereMonth('tanggalbooking', Carbon::now()->month)
            ->with('payment')
            ->get()
            ->sum(fn($b) => $b->payment->jumlah ?? 0);

        return view('owner.owner-programs', compact(
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
        ));
    }

    /**
     * Simpan program baru.
     */
    public function store(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $datavalid = $request->validate([
            'namalayanan'         => 'required|string|max:255',
            'harga'               => 'required|numeric|min:0',
            'durasi'              => 'required|integer|min:5|max:480',
            'idcategory'          => ['nullable', Rule::exists('categories', 'id')->where('idtenant', $tenant->id)],
            'deskripsi'           => 'nullable|string|max:1000',
            'cover_image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'staff_ids'           => 'nullable|array',
            'staff_ids.*'         => ['integer', Rule::exists('staff', 'id')->where('idtenant', $tenant->id)],
            'resource_ids'        => 'nullable|array',
            'resource_ids.*'      => ['integer', Rule::exists('resources', 'id')->where('idtenant', $tenant->id)],
            'additional_item_ids' => 'nullable|array',
            'additional_item_ids.*' => ['integer', Rule::exists('additional_items', 'id')->where('idtenant', $tenant->id)],
        ]);

        $subscription = \App\Models\Subscription::with('plan')->where('idtenant', $tenant->id)->latest()->first();
        if ($subscription && $subscription->plan) {
            $currentServices = Service::where('idtenant', $tenant->id)->count();
            if ($currentServices >= $subscription->plan->maxlayanan) {
                return back()->withErrors(['namalayanan' => 'Batas maksimum layanan (' . $subscription->plan->maxlayanan . ') telah tercapai. Silakan upgrade paket Anda.'])->withInput();
            }
        }

        // Handle cover image upload
        $imageUrl = null;
        if ($request->hasFile('cover_image')) {
            $imageUrl = $request->file('cover_image')->store('programs', 'public');
        }

        $service = Service::create([
            'idtenant'    => $tenant->id,
            'namalayanan' => $datavalid['namalayanan'],
            'harga'       => $datavalid['harga'],
            'durasi'      => $datavalid['durasi'],
            'idcategory'  => $datavalid['idcategory'] ?? null,
            'deskripsi'   => $datavalid['deskripsi'] ?? null,
            'image_url'   => $imageUrl,
        ]);

        if (!empty($datavalid['staff_ids'])) {
            $service->staff()->sync($datavalid['staff_ids']);
        }
        if (!empty($datavalid['resource_ids'])) {
            $service->resources()->sync($datavalid['resource_ids']);
        }
        if (!empty($datavalid['additional_item_ids'])) {
            $service->additionalItems()->sync($datavalid['additional_item_ids']);
        }

        // FS-030: Catat penambahan layanan ke usage_logs
        try {
            UsageLog::record($tenant->id, 'layanan');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal catat usage log layanan: ' . $e->getMessage());
        }

        // Invalidate customer-facing cache
        $this->clearServiceCache($tenant->id, $service->id);

        $redirectRoute = $request->is('*programs*') ? 'owner.programs' : 'owner.services';
        return redirect()->route($redirectRoute)->with('sukses', 'Program "' . $datavalid['namalayanan'] . '" berhasil ditambahkan!');
    }

    /**
     * Update program.
     */
    public function update(Request $request, int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $layanan = Service::where('idtenant', $tenant->id)->findOrFail($id);

        $datavalid = $request->validate([
            'namalayanan'         => 'required|string|max:255',
            'harga'               => 'required|numeric|min:0',
            'durasi'              => 'required|integer|min:5|max:480',
            'idcategory'          => ['nullable', Rule::exists('categories', 'id')->where('idtenant', $tenant->id)],
            'deskripsi'           => 'nullable|string|max:1000',
            'is_active'           => 'required|boolean',
            'cover_image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image'        => 'nullable|in:0,1',
            'staff_ids'           => 'nullable|array',
            'staff_ids.*'         => ['integer', Rule::exists('staff', 'id')->where('idtenant', $tenant->id)],
            'resource_ids'        => 'nullable|array',
            'resource_ids.*'      => ['integer', Rule::exists('resources', 'id')->where('idtenant', $tenant->id)],
            'additional_item_ids' => 'nullable|array',
            'additional_item_ids.*' => ['integer', Rule::exists('additional_items', 'id')->where('idtenant', $tenant->id)],
        ]);

        // Handle cover image
        $imageUrl = $layanan->image_url;

        if ($request->hasFile('cover_image')) {
            // Delete old image if exists
            if ($layanan->image_url) {
                Storage::disk('public')->delete($layanan->image_url);
            }
            $imageUrl = $request->file('cover_image')->store('programs', 'public');
        } elseif ($request->input('remove_image') === '1') {
            // Owner explicitly removed the image
            if ($layanan->image_url) {
                Storage::disk('public')->delete($layanan->image_url);
            }
            $imageUrl = null;
        }

        $layanan->update([
            'namalayanan' => $datavalid['namalayanan'],
            'harga'       => $datavalid['harga'],
            'durasi'      => $datavalid['durasi'],
            'idcategory'  => $datavalid['idcategory'] ?? null,
            'deskripsi'   => $datavalid['deskripsi'] ?? null,
            'is_active'   => (bool) $datavalid['is_active'],
            'image_url'   => $imageUrl,
        ]);

        if ($request->has('staff_ids')) {
            $layanan->staff()->sync($datavalid['staff_ids'] ?? []);
        }
        if ($request->has('resource_ids')) {
            $layanan->resources()->sync($datavalid['resource_ids'] ?? []);
        }
        if ($request->has('additional_item_ids')) {
            $layanan->additionalItems()->sync($datavalid['additional_item_ids'] ?? []);
        }

        // Invalidate customer-facing cache
        $this->clearServiceCache($tenant->id, $layanan->id);

        $redirectRoute = $request->is('*programs*') ? 'owner.programs' : 'owner.services';
        return redirect()->route($redirectRoute)->with('sukses', 'Program "' . $datavalid['namalayanan'] . '" berhasil diperbarui!');
    }

    /**
     * Hapus program.
     */
    public function destroy(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $layanan = Service::where('idtenant', $tenant->id)->findOrFail($id);
        $namalayanan = $layanan->namalayanan;

        $layanan->staff()->detach();
        $layanan->resources()->detach();
        $layanan->additionalItems()->detach();
        $layanan->delete();

        // Invalidate customer-facing cache
        $this->clearServiceCache($tenant->id, $id);

        $redirectRoute = request()->is('*programs*') ? 'owner.programs' : 'owner.services';
        return redirect()->route($redirectRoute)->with('sukses', 'Program "' . $namalayanan . '" berhasil dihapus!');
    }

    /**
     * Toggle status aktif program.
     */
    public function toggleStatus(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $layanan = Service::where('idtenant', $tenant->id)->findOrFail($id);
        $layanan->update([
            'is_active' => !$layanan->is_active,
        ]);

        $this->clearServiceCache($tenant->id, $layanan->id);
        $statusText = $layanan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $redirectRoute = request()->is('*programs*') ? 'owner.programs' : 'owner.services';
        return redirect()->route($redirectRoute)->with('sukses', 'Program "' . $layanan->namalayanan . '" berhasil ' . $statusText . '!');
    }
}
