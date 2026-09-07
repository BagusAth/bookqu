<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OwnerAssetController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $filter = $request->input('filter', 'all');
        $search = $request->input('search', '');

        $assets = Asset::where('idtenant', $tenant->id)
            ->when($filter !== 'all', function ($q) use ($filter) {
                $q->where('category', $filter);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->get();

        return view('owner.owner-assets', compact('tenant', 'assets', 'filter', 'search'));
    }

    public function store(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'file'     => 'required|file|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
            'title'    => 'nullable|string|max:150',
            'category' => 'nullable|string|in:logo,cover,service,gallery,other',
        ]);

        $uploadedFile = $request->file('file');
        $fileName = $uploadedFile->getClientOriginalName();
        $title = !empty($validated['title']) ? $validated['title'] : pathinfo($fileName, PATHINFO_FILENAME);
        $category = !empty($validated['category']) ? $validated['category'] : 'other';

        // Tenant-isolated storage path
        $path = $uploadedFile->store("tenants/{$tenant->id}/assets", 'public');

        Asset::create([
            'idtenant'  => $tenant->id,
            'title'     => $title,
            'category'  => $category,
            'file_path' => $path,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'file_size' => $uploadedFile->getSize(),
        ]);

        return redirect()->route('owner.settings.assets')
            ->with('sukses', 'File aset "' . $title . '" berhasil diunggah!');
    }

    public function destroy(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $asset = Asset::where('idtenant', $tenant->id)->findOrFail($id);
        $title = $asset->title;

        if ($asset->file_path && Storage::disk('public')->exists($asset->file_path)) {
            Storage::disk('public')->delete($asset->file_path);
        }

        $asset->delete();

        return redirect()->route('owner.settings.assets')
            ->with('sukses', 'Aset "' . $title . '" berhasil dihapus!');
    }
}
