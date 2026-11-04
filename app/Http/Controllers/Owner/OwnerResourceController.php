<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\Service;
use App\Support\TenantActor;
use App\Traits\ClearsBookingCache;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OwnerResourceController extends Controller
{
    use ClearsBookingCache;
    use ResolvesOwnerTenant;

    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $search = $request->input('search', '');

        $resources = Resource::where('idtenant', $tenant->id)
            ->with('services')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $services = Service::where('idtenant', $tenant->id)
            ->where('is_active', true)
            ->orderBy('namalayanan')
            ->get();

        return view('owner.resources', compact('tenant', 'resources', 'services', 'search'));
    }

    public function storeResource(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:9999',
            'location' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_ids.*' => ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $resource = Resource::create([
            'idtenant' => $tenant->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'capacity' => $validated['capacity'] ?? 1,
            'location' => $validated['location'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
        ]);

        if (! empty($validated['service_ids'])) {
            $resource->services()->sync($validated['service_ids']);
        }

        $this->clearActiveServicesCache($tenant->id);

        return redirect()->route('owner.resources.index')
            ->with('sukses', 'Resource "'.$resource->name.'" berhasil ditambahkan!');
    }

    public function updateResource(Request $request, int $id)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $resource = Resource::where('idtenant', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:9999',
            'location' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'service_ids' => 'nullable|array',
            'service_ids.*' => ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $resource->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'capacity' => $validated['capacity'] ?? 1,
            'location' => $validated['location'] ?? null,
            'is_active' => (bool) $validated['is_active'],
        ]);

        $changes = $resource->services()->sync($validated['service_ids'] ?? []);
        if (array_filter($changes)) {
            app(TenantActor::class)->record($resource, 'assignments_updated');
        }

        $this->clearActiveServicesCache($tenant->id);

        return redirect()->route('owner.resources.index')
            ->with('sukses', 'Resource "'.$resource->name.'" berhasil diperbarui!');
    }

    public function destroyResource(int $id)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $resource = Resource::where('idtenant', $tenant->id)->findOrFail($id);
        $name = $resource->name;
        $resource->services()->detach();
        $resource->delete();

        $this->clearActiveServicesCache($tenant->id);

        return redirect()->route('owner.resources.index')
            ->with('sukses', 'Resource "'.$name.'" berhasil dihapus!');
    }

    public function toggleResourceStatus(int $id)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $resource = Resource::where('idtenant', $tenant->id)->findOrFail($id);
        $resource->update(['is_active' => ! $resource->is_active]);

        $this->clearActiveServicesCache($tenant->id);

        $statusText = $resource->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('owner.resources.index')
            ->with('sukses', 'Resource "'.$resource->name.'" berhasil '.$statusText.'!');
    }
}
