<?php

namespace App\Actions\Team;

use App\Models\Tenant;
use App\Models\TenantMember;

final class CreateOwnerMembership
{
    public function execute(Tenant $tenant): void
    {
        // scope-bypass: creation establishes the owning membership for this newly stored tenant, before request context exists.
        TenantMember::withoutGlobalScopes()->firstOrCreate(['idtenant' => $tenant->id, 'iduser' => $tenant->iduser], ['role' => 'pemilik', 'accepted_at' => now()]);
    }
}
