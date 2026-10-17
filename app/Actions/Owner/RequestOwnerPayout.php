<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Models\OwnerPayout;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestOwnerPayout
{
    /**
     * Request payout withdrawal for tenant on platform mode.
     *
     * @throws ValidationException
     */
    public function execute(Tenant $tenant, float $amount): OwnerPayout
    {
        if (!is_finite($amount) || $amount < 10000 || abs($amount - round($amount, 2)) > 0.00001) {
            throw ValidationException::withMessages([
                'jumlah' => 'Nominal withdraw minimal Rp 10.000 dan maksimal dua angka desimal.',
            ]);
        }

        return DB::transaction(function () use ($tenant, $amount): OwnerPayout {
            // Serialize withdrawals for this tenant so parallel requests cannot
            // both spend the same previously-read balance.
            $lockedTenant = Tenant::query()->lockForUpdate()->find($tenant->id);
            if (!$lockedTenant) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Tenant tidak ditemukan.',
                ]);
            }

            if ($lockedTenant->payment_mode !== 'platform') {
                throw ValidationException::withMessages([
                    'jumlah' => 'Withdraw hanya tersedia untuk mode pembayaran platform.',
                ]);
            }

            $balance = (float) $lockedTenant->saldo_platform;
            $amount = round($amount, 2);
            if ($balance < $amount) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Saldo tidak mencukupi.',
                ]);
            }

            $lockedTenant->saldo_platform = round($balance - $amount, 2);
            $lockedTenant->save();

            return OwnerPayout::create([
                'idtenant'     => $lockedTenant->id,
                'jumlah'       => $amount,
                'status'       => 'pending',
                'requested_at' => now(),
            ]);
        });
    }
}
