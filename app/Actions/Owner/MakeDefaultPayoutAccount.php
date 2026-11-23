<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MakeDefaultPayoutAccount
{
    /**
     * Mark a payout account as the tenant's default payout account under tenant lock.
     * Note: changed_at must NOT change when setting an account as default.
     *
     * @throws ValidationException
     */
    public function execute(Tenant $tenant, TenantPayoutAccount $account): void
    {
        DB::transaction(function () use ($tenant, $account): void {
            $lockedTenant = Tenant::query()->lockForUpdate()->find($tenant->id);
            if (! $lockedTenant) {
                throw ValidationException::withMessages([
                    'rekening' => 'Tenant tidak ditemukan.',
                ]);
            }

            $target = TenantPayoutAccount::where('idtenant', $lockedTenant->id)->findOrFail($account->id);

            TenantPayoutAccount::where('idtenant', $lockedTenant->id)->update(['is_default' => false]);
            $target->is_default = true;
            $target->save();
        });
    }
}
