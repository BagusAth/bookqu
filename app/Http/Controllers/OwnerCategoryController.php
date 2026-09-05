<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OwnerCategoryController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $search = $request->input('search', '');
        $status = $request->input('status', 'all');
        $sort   = $request->input('sort', 'newest');

        $query = Category::where('idtenant', $tenant->id)
            ->withCount('services')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false));

        if ($sort === 'name_asc') {
            $query->orderBy('name', 'asc');
        } elseif ($sort === 'name_desc') {
            $query->orderBy('name', 'desc');
        } elseif ($sort === 'services') {
            $query->orderByDesc('services_count');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $categories = $query->get();

        return view('owner.owner-categories', compact('tenant', 'categories', 'search', 'status', 'sort'));
    }

    public function store(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'name'        => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->where('idtenant', $tenant->id),
            ],
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:50',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.unique' => 'Kategori dengan nama ini sudah ada.',
        ]);

        Category::create([
            'idtenant'    => $tenant->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'] ?? 'indigo',
            'is_active'   => $request->has('is_active') ? (bool) $request->input('is_active') : true,
        ]);

        return redirect()->route('owner.categories')->with('sukses', 'Kategori "' . $validated['name'] . '" berhasil ditambahkan!');
    }

    public function update(Request $request, int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $category = Category::where('idtenant', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name'        => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->where('idtenant', $tenant->id)->ignore($category->id),
            ],
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:50',
            'is_active'   => 'required|boolean',
        ], [
            'name.unique' => 'Kategori dengan nama ini sudah ada.',
        ]);

        $category->update([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'] ?? $category->color,
            'is_active'   => (bool) $validated['is_active'],
        ]);

        return redirect()->route('owner.categories')->with('sukses', 'Kategori "' . $category->name . '" berhasil diperbarui!');
    }

    public function destroy(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $category = Category::where('idtenant', $tenant->id)->findOrFail($id);
        $name = $category->name;

        // Unlink associated services safely
        $category->services()->update(['idcategory' => null]);
        $category->delete();

        return redirect()->route('owner.categories')->with('sukses', 'Kategori "' . $name . '" berhasil dihapus!');
    }

    public function toggleStatus(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $category = Category::where('idtenant', $tenant->id)->findOrFail($id);
        $category->update([
            'is_active' => !$category->is_active,
        ]);

        $statusText = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $category->is_active,
                'message' => 'Kategori "' . $category->name . '" berhasil ' . $statusText . '.',
            ]);
        }

        return redirect()->route('owner.categories')->with('sukses', 'Kategori "' . $category->name . '" berhasil ' . $statusText . '!');
    }
}
