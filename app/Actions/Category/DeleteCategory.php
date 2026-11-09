<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    use ClearsBookingCache;

    /**
     * Delete an empty category (FR-CATEGORY-003, owner-portal.md §8.6 "delete when empty").
     *
     * @return string Name of the deleted category
     *
     * @throws ValidationException when services still use the category
     */
    public function execute(Tenant $tenant, int $categoryId): string
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $category = Category::where('idtenant', $tenant->id)->findOrFail($categoryId);

        // scope-bypass: filtered by the authenticated tenant; inactive services count as members too
        $serviceCount = Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->where('idcategory', $category->id)->count();
        if ($serviceCount > 0) {
            throw ValidationException::withMessages([
                'error' => 'Kategori "'.$category->name.'" masih dipakai '.$serviceCount.' layanan. Pindahkan layanannya ke kategori lain atau nonaktifkan kategori ini.',
            ]);
        }

        $name = $category->name;
        UpdateCategory::deleteStoredImage($category->image_url);
        $category->delete();

        $this->clearActiveServicesCache($tenant->id);

        return $name;
    }
}
