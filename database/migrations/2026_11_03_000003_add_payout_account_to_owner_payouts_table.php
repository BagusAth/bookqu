<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-123: Link owner payouts to saved payout account with bank code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_payouts', function (Blueprint $table): void {
            $table->foreignId('payout_account_id')->nullable()->after('request_key')->constrained('tenant_payout_accounts')->nullOnDelete();
            $table->string('bank_kode', 20)->nullable()->after('payout_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('owner_payouts', function (Blueprint $table): void {
            $table->dropForeign(['payout_account_id']);
            $table->dropColumn(['payout_account_id', 'bank_kode']);
        });
    }
};
