<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Domain\Payout\PayoutState;
use App\Models\OwnerPayout;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use App\Notifications\PayoutAccountChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteTenantPayoutAccount
{
    /**
     * Soft-delete a tenant's payout bank account under tenant lock.
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

            // Reload scoped account
            $account = TenantPayoutAccount::where('idtenant', $lockedTenant->id)->findOrFail($account->id);

            // Check if attached to in-flight payout
            $inFlight = OwnerPayout::where('idtenant', $lockedTenant->id)
                ->where('payout_account_id', $account->id)
                ->whereIn('status', [PayoutState::PENDING, PayoutState::APPROVED])
                ->exists();

            if ($inFlight) {
                throw ValidationException::withMessages([
                    'rekening' => 'Rekening sedang dipakai pengajuan withdraw yang berjalan.',
                ]);
            }

            $wasDefault = (bool) $account->is_default;
            $bankNama = (string) $account->bank_nama;
            $maskedNumber = $account->masked_number;

            $account->delete();

            if ($wasDefault) {
                $next = TenantPayoutAccount::where('idtenant', $lockedTenant->id)->latest()->first();
                if ($next) {
                    $next->update(['is_default' => true]);
                }
            }

            DB::afterCommit(function () use ($lockedTenant, $bankNama, $maskedNumber): void {
                $owner = $lockedTenant->user;
                if ($owner instanceof User) {
                    $owner->notify(new PayoutAccountChangedNotification(
                        'dihapus',
                        $bankNama,
                        $maskedNumber
                    ));
                }
            });
        });
    }
}
