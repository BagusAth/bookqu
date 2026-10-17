<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Models\OwnerPayout;
use App\Models\Tenant;
use App\Support\TenantContext;
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
        app(TenantContext::class)->setTenantId($tenant->id);

        if ($tenant->payment_mode !== 'platform') {
            throw ValidationException::withMessages([
                'jumlah' => 'Withdraw hanya tersedia untuk mode pembayaran platform.',
            ]);
        }

        if ((float) $tenant->saldo_platform < $amount) {
            throw ValidationException::withMessages([
                'jumlah' => 'Saldo tidak mencukupi.',
            ]);
        }

        $tenant->saldo_platform = (float) $tenant->saldo_platform - $amount;
        $tenant->save();

        return OwnerPayout::create([
            'idtenant'     => $tenant->id,
            'jumlah'       => $amount,
            'status'       => 'pending',
            'requested_at' => now(),
        ]);
    }
}
