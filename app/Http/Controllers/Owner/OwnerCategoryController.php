<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Category\CreateCategory;
use App\Actions\Category\DeleteCategory;
use App\Actions\Category\ToggleCategoryStatus;
use App\Actions\Category\UpdateCategory;
use App\Actions\Service\GetLayananPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerCategoryController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * The Layanan page opened on its Kategori tab (owner-portal.md §8.6).
     */
    public function index(Request $request, GetLayananPage $getLayananPage): View
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $tab = $request->query('tab') === GetLayananPage::TAB_SERVICES ? GetLayananPage::TAB_SERVICES : GetLayananPage::TAB_CATEGORIES;

        return view('owner.services', array_merge(
            $getLayananPage->execute($tenant, $request->only(['katakunci', 'category', 'search', 'status', 'sort'])),
            ['tenant' => $tenant, 'activeTab' => $tab],
        ));
    }

    public function store(StoreCategoryRequest $request, CreateCategory $createCategory): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $category = $createCategory->execute($tenant, $request->validated(), $request->file('image'));

        return redirect()->route('owner.categories')->with('sukses', 'Kategori "'.$category->name.'" berhasil ditambahkan!');
    }

    public function update(UpdateCategoryRequest $request, int $id, UpdateCategory $updateCategory): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $category = $updateCategory->execute($tenant, $id, $request->validated(), $request->file('image'), $request->input('remove_image') === '1');

        return redirect()->route('owner.categories')->with('sukses', 'Kategori "'.$category->name.'" berhasil diperbarui!');
    }

    public function destroy(int $id, DeleteCategory $deleteCategory): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $name = $deleteCategory->execute($tenant, $id);

        return redirect()->route('owner.categories')->with('sukses', 'Kategori "'.$name.'" berhasil dihapus!');
    }

    public function toggleStatus(Request $request, int $id, ToggleCategoryStatus $toggleCategoryStatus): RedirectResponse|JsonResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $category = $toggleCategoryStatus->execute($tenant, $id);
        $statusText = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $category->is_active,
                'message' => 'Kategori "'.$category->name.'" berhasil '.$statusText.'.',
            ]);
        }

        return redirect()->route('owner.categories')->with('sukses', 'Kategori "'.$category->name.'" berhasil '.$statusText.'!');
    }
}
