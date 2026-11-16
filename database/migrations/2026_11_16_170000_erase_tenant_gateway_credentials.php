<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BQ-157 (D-011, D-016): businesses no longer bring their own Midtrans account. Every payment
 * uses the platform gateway, so stored tenant credentials are erased and every tenant is on
 * the platform mode. The erased keys cannot be restored; down() leaves the data as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenants')->update([
            'payment_mode' => 'platform',
            'midtrans_sandbox_merchant_id' => null,
            'midtrans_sandbox_client_key' => null,
            'midtrans_sandbox_server_key' => null,
            'midtrans_prod_merchant_id' => null,
            'midtrans_prod_client_key' => null,
            'midtrans_prod_server_key' => null,
        ]);
    }

    public function down(): void
    {
        // Erased credentials cannot be restored.
    }
};
