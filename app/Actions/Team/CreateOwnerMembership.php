<?php

namespace App\Actions\Team;

use App\Models\Tenant;
use App\Models\TenantMember;
use App\Support\TenantContext;

final class CreateOwnerMembership
{
    public function execute(Tenant $tenant): void
    {
        app(TenantContext::class)->run((int) $tenant->id, fn () => TenantMember::firstOrCreate(
            ['idtenant' => $tenant->id, 'iduser' => $tenant->iduser], ['role' => 'pemilik', 'accepted_at' => now()],
        ));
    }
}
