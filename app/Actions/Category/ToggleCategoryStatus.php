<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;

class ToggleCategoryStatus
{
    use ClearsBookingCache;

    /**
     * Activate or deactivate a category of the tenant (FR-CATEGORY-004).
     */
    public function execute(Tenant $tenant, int $categoryId): Category
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $category = Category::where('idtenant', $tenant->id)->findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);

        $this->clearActiveServicesCache($tenant->id);

        return $category;
    }
}
