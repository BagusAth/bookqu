<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-103: Idempotent payout request key per tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_payouts', function (Blueprint $table): void {
            $table->uuid('request_key')->nullable()->after('idtenant');
            $table->unique(['idtenant', 'request_key']);
        });
    }

    public function down(): void
    {
        Schema::table('owner_payouts', function (Blueprint $table): void {
            $table->dropUnique(['idtenant', 'request_key']);
            $table->dropColumn('request_key');
        });
    }
};
