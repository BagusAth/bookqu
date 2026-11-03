<?php

namespace App\Traits;

use App\Actions\Team\ResolveTenantMembership;
use App\Models\Tenant;
use App\Support\TenantContext;

trait ResolvesOwnerTenant
{
    /**
     * Resolve and authorize the tenant for the authenticated owner.
     * Ensure that the resolved tenant belongs to the currently logged in user.
     */
    protected function resolveTenant(): ?Tenant
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }
        $member = app(ResolveTenantMembership::class)->execute($user);
        if ($member === null) {
            return null;
        }
        app(TenantContext::class)->setTenantId((int) $member->idtenant);

        return Tenant::with('user')->find($member->idtenant);
    }
}
