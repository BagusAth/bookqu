<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Domain\Payout\PayoutEligibility;
use App\Models\OwnerPayout;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestOwnerPayout
{
    /**
     * Request payout withdrawal for tenant on platform mode. The amount is held (debited)
     * at request time; a rejected or failed payout returns it (BQ-015).
     *
     * @throws ValidationException
     */
    public function execute(
        Tenant $tenant,
        int|float $amount,
        TenantPayoutAccount $account,
        string $requestKey,
        ?User $user = null
    ): OwnerPayout {
        if (! is_finite($amount) || $amount < 10000 || floor((float) $amount) != $amount) {
            throw ValidationException::withMessages([
                'jumlah' => 'Nominal withdraw harus berupa angka bulat dan minimal Rp 10.000.',
            ]);
        }

        $intAmount = (int) $amount;

        return DB::transaction(function () use ($tenant, $intAmount, $account, $requestKey, $user): OwnerPayout {
            // Serialize withdrawals for this tenant so parallel requests cannot
            // both spend the same previously-read balance.
            $lockedTenant = Tenant::query()->lockForUpdate()->find($tenant->id);
            if (! $lockedTenant) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Tenant tidak ditemukan.',
                ]);
            }

            // Return existing payout if duplicate request_key is submitted (idempotency)
            $existing = OwnerPayout::where('idtenant', $lockedTenant->id)
                ->where('request_key', $requestKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            // Verify eligibility reasons under lock
            $owner = $user ?? ($lockedTenant->user instanceof User ? $lockedTenant->user : null);
            $reasons = PayoutEligibility::reasons($lockedTenant, $owner);
            if (! empty($reasons)) {
                throw ValidationException::withMessages([
                    'jumlah' => $reasons[0],
                ]);
            }

            // Check max amount per request
            $maxAmount = config('payouts.max_amount_per_request');
            if ($maxAmount !== null && is_numeric($maxAmount) && $intAmount > (int) $maxAmount) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Nominal melebihi batas Rp '.number_format((float) $maxAmount, 0, ',', '.').' per pengajuan.',
                ]);
            }

            // Check max requests per day (Asia/Jakarta)
            $maxPerDay = config('payouts.max_requests_per_day');
            if ($maxPerDay !== null && is_numeric($maxPerDay)) {
                $todayStart = now('Asia/Jakarta')->startOfDay();
                $todayEnd = now('Asia/Jakarta')->endOfDay();
                // scope-bypass: count today's payouts for locked tenant
                $requestsToday = OwnerPayout::withoutGlobalScopes()
                    ->where('idtenant', $lockedTenant->id)
                    ->whereBetween('requested_at', [$todayStart, $todayEnd])
                    ->count();

                if ($requestsToday >= (int) $maxPerDay) {
                    throw ValidationException::withMessages([
                        'jumlah' => 'Batas '.((int) $maxPerDay).' pengajuan withdraw per hari sudah tercapai.',
                    ]);
                }
            }

            // Reload scoped account under tenant lock
            // scope-bypass: load payout account for locked tenant
            $lockedAccount = TenantPayoutAccount::withoutGlobalScopes()
                ->where('idtenant', $lockedTenant->id)
                ->whereNull('deleted_at')
                ->find($account->id);

            if (! $lockedAccount) {
                throw ValidationException::withMessages([
                    'payout_account_id' => 'Rekening tujuan tidak valid.',
                ]);
            }

            $balance = (float) $lockedTenant->saldo_platform;
            if ($balance < $intAmount) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Saldo tidak mencukupi.',
                ]);
            }

            $lockedTenant->saldo_platform = round($balance - $intAmount, 2);
            $lockedTenant->save();

            return OwnerPayout::create([
                'idtenant' => $lockedTenant->id,
                'request_key' => $requestKey,
                'payout_account_id' => $lockedAccount->id,
                'bank_kode' => $lockedAccount->bank_kode,
                'bank_nama' => $lockedAccount->bank_nama,
                'nomor_rekening' => $lockedAccount->nomor_rekening,
                'nama_pemilik_rekening' => $lockedAccount->nama_pemilik_rekening,
                'jumlah' => $intAmount,
                'status' => 'pending',
                'requested_at' => now(),
            ]);
        });
    }
}
