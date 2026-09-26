<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;

class ToggleServiceStatus
{
    use ClearsBookingCache;

    /**
     * Toggle active status of a service.
     *
     * @param Service|int|string $service
     */
    public function execute(Tenant $tenant, Service|int|string $service): Service
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $targetId = $service instanceof Service ? $service->id : (int) $service;
        $layanan = Service::withoutGlobalScopes()
            ->where('idtenant', $tenant->id)
            ->findOrFail($targetId);

        $layanan->update([
            'is_active' => !$layanan->is_active,
        ]);

        $this->clearServiceCache($tenant->id, $layanan->id);

        return $layanan;
    }
}
