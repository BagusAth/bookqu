<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Service;
use App\Models\Staff;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OwnerStaffResourceController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $tab = $request->input('tab', 'staff');
        $search = $request->input('search', '');

        $staff = Staff::where('idtenant', $tenant->id)
            ->with('services')
            ->when($search && $tab === 'staff', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $resources = Resource::where('idtenant', $tenant->id)
            ->with('services')
            ->when($search && $tab === 'resources', function ($q) use ($search) {
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

        return view('owner.owner-staff-resources', compact('tenant', 'staff', 'resources', 'services', 'tab', 'search'));
    }

    public function storeStaff(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'name'                  => 'required|string|max:100',
            'role'                  => 'required|string|max:100',
            'phone'                 => 'nullable|string|max:30',
            'email'                 => 'nullable|email|max:100',
            'availability'          => 'nullable|string|max:255',
            'availability_schedule' => 'nullable|string|max:255',
            'is_active'             => 'nullable|boolean',
            'service_ids'           => 'nullable|array',
            'service_ids.*'         => ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $avail = $validated['availability_schedule'] ?? $validated['availability'] ?? null;

        $staff = Staff::create([
            'idtenant'              => $tenant->id,
            'name'                  => $validated['name'],
            'role'                  => $validated['role'],
            'phone'                 => $validated['phone'] ?? null,
            'email'                 => $validated['email'] ?? null,
            'availability_schedule' => $avail,
            'is_active'             => $request->has('is_active') ? (bool) $request->input('is_active') : true,
        ]);

        if (!empty($validated['service_ids'])) {
            $staff->services()->sync($validated['service_ids']);
        }

        return redirect()->route('owner.staff-resources', ['tab' => 'staff'])
            ->with('sukses', 'Staff "' . $staff->name . '" berhasil ditambahkan!');
    }

    public function updateStaff(Request $request, int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $staff = Staff::where('idtenant', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name'                  => 'required|string|max:100',
            'role'                  => 'required|string|max:100',
            'phone'                 => 'nullable|string|max:30',
            'email'                 => 'nullable|email|max:100',
            'availability'          => 'nullable|string|max:255',
            'availability_schedule' => 'nullable|string|max:255',
            'is_active'             => 'required|boolean',
            'service_ids'           => 'nullable|array',
            'service_ids.*'         => ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $avail = $validated['availability_schedule'] ?? $validated['availability'] ?? null;

        $staff->update([
            'name'                  => $validated['name'],
            'role'                  => $validated['role'],
            'phone'                 => $validated['phone'] ?? null,
            'email'                 => $validated['email'] ?? null,
            'availability_schedule' => $avail,
            'is_active'             => (bool) $validated['is_active'],
        ]);

        $staff->services()->sync($validated['service_ids'] ?? []);

        return redirect()->route('owner.staff-resources', ['tab' => 'staff'])
            ->with('sukses', 'Staff "' . $staff->name . '" berhasil diperbarui!');
    }

    public function destroyStaff(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $staff = Staff::where('idtenant', $tenant->id)->findOrFail($id);
        $name = $staff->name;
        $staff->services()->detach();
        $staff->delete();

        return redirect()->route('owner.staff-resources', ['tab' => 'staff'])
            ->with('sukses', 'Staff "' . $name . '" berhasil dihapus!');
    }

    public function toggleStaffStatus(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $staff = Staff::where('idtenant', $tenant->id)->findOrFail($id);
        $staff->update(['is_active' => !$staff->is_active]);

        $statusText = $staff->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('owner.staff-resources', ['tab' => 'staff'])
            ->with('sukses', 'Staff "' . $staff->name . '" berhasil ' . $statusText . '!');
    }

    public function storeResource(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'type'         => 'required|string|max:100',
            'capacity'     => 'nullable|integer|min:1|max:9999',
            'location'     => 'nullable|string|max:255',
            'is_active'    => 'nullable|boolean',
            'service_ids'  => 'nullable|array',
            'service_ids.*'=> ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $resource = Resource::create([
            'idtenant'  => $tenant->id,
            'name'      => $validated['name'],
            'type'      => $validated['type'],
            'capacity'  => $validated['capacity'] ?? 1,
            'location'  => $validated['location'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
        ]);

        if (!empty($validated['service_ids'])) {
            $resource->services()->sync($validated['service_ids']);
        }

        return redirect()->route('owner.staff-resources', ['tab' => 'resources'])
            ->with('sukses', 'Resource "' . $resource->name . '" berhasil ditambahkan!');
    }

    public function updateResource(Request $request, int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $resource = Resource::where('idtenant', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'type'         => 'required|string|max:100',
            'capacity'     => 'nullable|integer|min:1|max:9999',
            'location'     => 'nullable|string|max:255',
            'is_active'    => 'required|boolean',
            'service_ids'  => 'nullable|array',
            'service_ids.*'=> ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $resource->update([
            'name'      => $validated['name'],
            'type'      => $validated['type'],
            'capacity'  => $validated['capacity'] ?? 1,
            'location'  => $validated['location'] ?? null,
            'is_active' => (bool) $validated['is_active'],
        ]);

        $resource->services()->sync($validated['service_ids'] ?? []);

        return redirect()->route('owner.staff-resources', ['tab' => 'resources'])
            ->with('sukses', 'Resource "' . $resource->name . '" berhasil diperbarui!');
    }

    public function destroyResource(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $resource = Resource::where('idtenant', $tenant->id)->findOrFail($id);
        $name = $resource->name;
        $resource->services()->detach();
        $resource->delete();

        return redirect()->route('owner.staff-resources', ['tab' => 'resources'])
            ->with('sukses', 'Resource "' . $name . '" berhasil dihapus!');
    }

    public function toggleResourceStatus(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $resource = Resource::where('idtenant', $tenant->id)->findOrFail($id);
        $resource->update(['is_active' => !$resource->is_active]);

        $statusText = $resource->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('owner.staff-resources', ['tab' => 'resources'])
            ->with('sukses', 'Resource "' . $resource->name . '" berhasil ' . $statusText . '!');
    }
}
