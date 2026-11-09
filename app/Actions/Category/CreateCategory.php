<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use App\Models\Tenant;
use App\Support\CategoryColor;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Http\UploadedFile;

class CreateCategory
{
    use ClearsBookingCache;

    /**
     * Create a category with its colour label, optional image and display order (FR-CATEGORY-001, FR-CATEGORY-006).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Tenant $tenant, array $data, ?UploadedFile $image = null): Category
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $category = Category::create([
            'idtenant' => $tenant->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? CategoryColor::DEFAULT,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'image_url' => $image?->store('categories', 'public'),
            'is_active' => array_key_exists('is_active', $data) && $data['is_active'] !== null ? (bool) $data['is_active'] : true,
        ]);

        $this->clearActiveServicesCache($tenant->id);

        return $category;
    }
}
