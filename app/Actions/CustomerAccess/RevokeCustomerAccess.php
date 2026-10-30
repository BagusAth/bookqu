<?php

namespace App\Actions\CustomerAccess;

use App\Actions\Schedule\ClaimSlots;
use App\Models\CustomerAccessGrant;
use Illuminate\Support\Facades\DB;

final class RevokeCustomerAccess
{
    public function execute(CustomerAccessGrant $grant, bool $wholeFamily = false): void
    {
        DB::transaction(function () use ($grant, $wholeFamily): void {
            ClaimSlots::lockTenant((int) $grant->idtenant);
            if ($wholeFamily) {
                $table = $grant->payment_id !== null ? 'payments' : 'bookings';
                $id = $grant->payment_id ?? $grant->booking_id;
                DB::table($table)->where('idtenant', $grant->idtenant)->where('id', $id)->increment('customer_access_version');
            } else {
                $grant->update(['revoked_at' => now()]);
            }
        });
    }
}
