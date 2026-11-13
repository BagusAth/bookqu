<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Booking\BookingPricingReservations;
use App\Actions\Schedule\ClaimSlots;
use App\Http\Controllers\Controller;
use App\Models\AdditionalItem;
use App\Models\Service;
use App\Support\TenantActor;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OwnerAdditionalItemController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $search = $request->input('search', '');

        $items = AdditionalItem::where('idtenant', $tenant->id)
            ->with('services')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $services = Service::where('idtenant', $tenant->id)
            ->where('is_active', true)
            ->orderBy('namalayanan')
            ->get();

        return view('owner.additional-items', compact('tenant', 'items', 'services', 'search'));
    }

    public function store(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_ids.*' => ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $item = AdditionalItem::create([
            'idtenant' => $tenant->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'] ?? null,
            'is_unlimited' => ! isset($validated['stock']),
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
        ]);

        if (! empty($validated['service_ids'])) {
            $item->services()->sync($validated['service_ids']);
        }

        return redirect()->route('owner.additional-items')
            ->with('sukses', 'Add-on "'.$item->name.'" berhasil ditambahkan!');
    }

    public function update(Request $request, int $id)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $item = AdditionalItem::where('idtenant', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'service_ids' => 'nullable|array',
            'service_ids.*' => ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        DB::transaction(function () use ($tenant, $item, $validated): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            $held = app(BookingPricingReservations::class)->held('additional_item_id', $item->id);
            if ($held > 0 && (int) ($validated['stock'] ?? 0) < $held) {
                throw ValidationException::withMessages(['stock' => 'Stok masih dipesan oleh pembayaran aktif.']);
            }
            $item->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'stock' => $validated['stock'] ?? null,
                'is_unlimited' => ! isset($validated['stock']),
                'is_active' => (bool) $validated['is_active'],
            ]);

            $changes = $item->services()->sync($validated['service_ids'] ?? []);
            if (array_filter($changes)) {
                app(TenantActor::class)->record($item, 'assignments_updated');
            }

        });

        return redirect()->route('owner.additional-items')
            ->with('sukses', 'Add-on "'.$item->name.'" berhasil diperbarui!');
    }

    public function destroy(int $id)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $item = AdditionalItem::where('idtenant', $tenant->id)->findOrFail($id);
        $name = $item->name;
        DB::transaction(function () use ($tenant, $item): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            if (app(BookingPricingReservations::class)->held('additional_item_id', $item->id) > 0) {
                throw ValidationException::withMessages(['stock' => 'Add-on masih dipesan oleh pembayaran aktif.']);
            }
            $item->delete();
        });

        return redirect()->route('owner.additional-items')
            ->with('sukses', 'Add-on "'.$name.'" berhasil dihapus!');
    }

    public function toggleStatus(int $id)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $item = AdditionalItem::where('idtenant', $tenant->id)->findOrFail($id);
        DB::transaction(function () use ($tenant, $item): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            $item->refresh();
            $item->update(['is_active' => ! $item->is_active]);
        });

        $statusText = $item->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('owner.additional-items')
            ->with('sukses', 'Add-on "'.$item->name.'" berhasil '.$statusText.'!');
    }
}
