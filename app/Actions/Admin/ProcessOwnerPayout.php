<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Domain\Payout\PayoutState;
use App\Models\OwnerPayout;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\OwnerPayoutStatusChangedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Admin review of an owner payout (BQ-015). Transitions follow PayoutState; rejected and
 * failed payouts return their held amount to the tenant balance under the tenant lock.
 */
class ProcessOwnerPayout
{
    /**
     * @param  array{rejection_reason?: ?string, transfer_reference?: ?string}  $data
     * @return bool false when the transition is not allowed from the payout's current state
     */
    public function execute(int $payoutId, string $to, User $admin, array $data = [], ?UploadedFile $proof = null): bool
    {
        $payout = DB::transaction(function () use ($payoutId, $to, $admin, $data, $proof): ?OwnerPayout {
            // scope-bypass: platform admin (role:admin route) reviews payouts of every tenant
            $tenantId = OwnerPayout::withoutGlobalScopes()->whereKey($payoutId)->value('idtenant');
            if ($tenantId === null) {
                return null;
            }

            // Tenant first, then the payout: the same order RequestOwnerPayout uses
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->first();
            // scope-bypass: platform admin, payout re-read under the tenant lock
            $payout = OwnerPayout::withoutGlobalScopes()->whereKey($payoutId)->lockForUpdate()->first();

            if ($payout === null || ! PayoutState::canTransition((string) $payout->status, $to)) {
                return null;
            }

            $payout->fill([
                'status' => $to,
                'processed_at' => now(),
                'processed_by' => $admin->id,
                'rejection_reason' => $data['rejection_reason'] ?? $payout->rejection_reason,
                'transfer_reference' => $data['transfer_reference'] ?? $payout->transfer_reference,
            ]);
            if ($proof !== null) {
                $payout->proof_path = $proof->store('payout-proofs', 'local');
            }
            $payout->save();

            if (PayoutState::returnsFunds($to)) {
                Tenant::query()->whereKey($tenantId)->increment('saldo_platform', (float) $payout->jumlah);
            }

            return $payout;
        });

        if ($payout === null) {
            return false;
        }

        try {
            $owner = $payout->tenant instanceof Tenant ? $payout->tenant->user : null;
            if ($owner instanceof User) {
                $owner->notify(new OwnerPayoutStatusChangedNotification($payout));
            }
        } catch (\Throwable $e) {
            Log::warning('ProcessOwnerPayout: owner notification failed', ['exception_type' => $e::class, 'payout_id' => $payout->id]);
        }

        return true;
    }
}
