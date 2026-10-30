<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Actions\Schedule\ClaimSlots;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Support\Facades\DB;

class ToggleServiceStatus
{
    use ClearsBookingCache;

    /**
     * Toggle active status of a service.
     */
    public function execute(Tenant $tenant, Service|int|string $service): Service
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $targetId = $service instanceof Service ? $service->id : (int) $service;
        $layanan = Service::withoutGlobalScopes()
            ->where('idtenant', $tenant->id)
            ->findOrFail($targetId);

        // Eligibility changes share the tenant-first protocol with slot claims (ADR-0005 decision 4)
        DB::transaction(function () use ($tenant, $layanan) {
            ClaimSlots::lockTenant((int) $tenant->id);
            $layanan->refresh();
            $layanan->update([
                'is_active' => ! $layanan->is_active,
            ]);
        });

        $this->clearServiceCache($tenant->id, $layanan->id);

        return $layanan;
    }
}
