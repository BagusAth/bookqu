<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BQ-079 (lead decision 2026-11-01): tenant-owned Midtrans mode is not offered in v1.
 * Every tenant pays through the platform; stored tenant keys are kept (encrypted) for a later release.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenants')->where('payment_mode', 'owner')->update(['payment_mode' => 'platform']);
    }

    public function down(): void
    {
        // Irreversible on purpose: which tenants chose `owner` is not needed once the mode is withdrawn.
    }
};
