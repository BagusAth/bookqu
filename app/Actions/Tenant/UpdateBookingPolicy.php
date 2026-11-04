<?php

namespace App\Actions\Tenant;

use App\Actions\Schedule\ClaimSlots;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

final class UpdateBookingPolicy
{
    /** @param array<string, mixed> $policy */
    public function execute(Tenant $tenant, array $policy): void
    {
        DB::transaction(function () use ($tenant, $policy): void {
            ClaimSlots::lockTenant($tenant->id);
            $tenant->update($policy);
            DB::afterCommit(fn () => app(GetStorefrontTenant::class)->forget($tenant->id));
        });
    }
}
