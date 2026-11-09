<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateCategory
{
    use ClearsBookingCache;

    /**
     * Update a category of the tenant (FR-CATEGORY-002, FR-CATEGORY-006). A new image replaces the stored one.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Tenant $tenant, int $categoryId, array $data, ?UploadedFile $image = null, bool $removeImage = false): Category
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $category = Category::where('idtenant', $tenant->id)->findOrFail($categoryId);

        $imageUrl = $category->image_url;
        if ($image !== null || $removeImage) {
            self::deleteStoredImage($imageUrl);
            $imageUrl = $image?->store('categories', 'public');
        }

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? $category->color,
            'sort_order' => array_key_exists('sort_order', $data) && $data['sort_order'] !== null ? (int) $data['sort_order'] : $category->sort_order,
            'image_url' => $imageUrl,
            'is_active' => (bool) $data['is_active'],
        ]);

        $this->clearActiveServicesCache($tenant->id);

        return $category;
    }

    public static function deleteStoredImage(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
