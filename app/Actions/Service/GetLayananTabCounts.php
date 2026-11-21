<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Models\AdditionalItem;
use App\Models\Category;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;

/**
 * Counts beside the Layanan, Kategori and Tambahan tabs (BQ-162, Papan Jadwal prototype).
 */
class GetLayananTabCounts
{
    /** @return array{layanan: int, kategori: int, add-ons: int} */
    public function execute(Tenant $tenant): array
    {
        return app(TenantContext::class)->run((int) $tenant->id, fn (): array => [
            'layanan' => Service::query()->where('idtenant', $tenant->id)->count(),
            'kategori' => Category::query()->where('idtenant', $tenant->id)->count(),
            'add-ons' => AdditionalItem::query()->where('idtenant', $tenant->id)->count(),
        ]);
    }
}
