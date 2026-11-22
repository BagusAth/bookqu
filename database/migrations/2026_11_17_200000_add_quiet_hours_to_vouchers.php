<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-162 batch 4 (FR-VOUCHER-006, D-026): a promo can be limited to days of the week
 * (ISO 1 = Monday … 7 = Sunday, comma-separated) and a start-time range.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->string('valid_days', 20)->nullable();
            $table->time('valid_from_time')->nullable();
            $table->time('valid_until_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', fn (Blueprint $table) => $table->dropColumn(['valid_days', 'valid_from_time', 'valid_until_time']));
    }
};
