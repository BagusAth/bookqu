<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Ownership proof for a business's own domain (BQ-154, Tech Lead 2026-11-16): the domain connects
 * only when a TXT record at `_bookqu.<domain>` carries this business's token.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('custom_domain_token', 64)->nullable()->after('custom_domain_verified_at');
        });

        // Domains saved before this check get a token, so their owners can add the TXT record.
        foreach (DB::table('tenants')->whereNotNull('custom_domain')->pluck('id') as $tenantId) {
            DB::table('tenants')->where('id', $tenantId)->update(['custom_domain_token' => Str::lower(Str::random(32))]);
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('custom_domain_token');
        });
    }
};
