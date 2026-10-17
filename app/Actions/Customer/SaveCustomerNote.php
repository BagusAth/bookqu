<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\CustomerNote;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class SaveCustomerNote
{
    /**
     * Save or update an internal owner note for a customer.
     * IDOR-protected by checking customer booking ownership.
     */
    public function execute(Tenant $tenant, string $customerIdentifier, ?string $notes): CustomerNote
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $identifier = strtolower(trim($customerIdentifier));

        // IDOR guard before write
        $exists = DB::table('bookings')
            ->where('idtenant', $tenant->id)
            ->where(function ($q) use ($identifier) {
                $q->whereRaw("LOWER(TRIM(COALESCE(NULLIF(TRIM(email), ''), NULLIF(TRIM(nomorhp), ''), CONCAT('guest-', id)))) = ?", [$identifier]);
            })
            ->exists();

        if (!$exists) {
            abort(403, 'Akses tidak diizinkan.');
        }

        return CustomerNote::updateOrCreate(
            [
                'idtenant'            => $tenant->id,
                'customer_identifier' => $identifier,
            ],
            [
                'notes' => $notes ?? '',
            ]
        );
    }
}
