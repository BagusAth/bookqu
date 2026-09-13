<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Support\TenantContext;

trait ResolvesOwnerTenant
{
    /**
     * Resolve and authorize the tenant for the authenticated owner.
     * Ensure that the resolved tenant belongs to the currently logged in user.
     *
     * @return Tenant|null
     */
    protected function resolveTenant(): ?Tenant
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return null;
        }

        // P0-02: Use TenantContext instead of session
        $tenantId = app(TenantContext::class)->getTenantId();

        if (is_numeric($tenantId)) {
            $tenant = Tenant::with('user')->find($tenantId);
            // P0-15: Ensure ownership
            if ($tenant && $tenant->iduser === $userId) {
                return $tenant;
            }
        }

        // Fallback: get the first tenant owned by the user
        $tenant = Tenant::with('user')->where('iduser', $userId)->first();
        
        if ($tenant) {
            app(TenantContext::class)->setTenantId($tenant->id);
            return $tenant;
        }

        return null;
    }
}
