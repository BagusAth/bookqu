<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Models\AdditionalItem;
use App\Models\Category;
use App\Models\Service;
use App\Models\Tenant;

/**
 * Counts beside the Layanan, Kategori and Tambahan tabs (BQ-162, Papan Jadwal prototype).
 */
class GetLayananTabCounts
{
    /** @return array{layanan: int, kategori: int, add-ons: int} */
    public function execute(Tenant $tenant): array
    {
        return [
            // scope-bypass: filtered by the authenticated tenant
            'layanan' => Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->count(),
            // scope-bypass: filtered by the authenticated tenant
            'kategori' => Category::withoutGlobalScopes()->where('idtenant', $tenant->id)->count(),
            // scope-bypass: filtered by the authenticated tenant
            'add-ons' => AdditionalItem::withoutGlobalScopes()->where('idtenant', $tenant->id)->count(),
        ];
    }
}
