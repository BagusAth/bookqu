<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-015: payout review data and the idempotent balance-credit marker on payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_payouts', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->change();
            $table->string('bank_nama', 100)->nullable()->after('jumlah');
            $table->string('nomor_rekening', 50)->nullable()->after('bank_nama');
            $table->string('nama_pemilik_rekening', 150)->nullable()->after('nomor_rekening');
            $table->string('rejection_reason', 500)->nullable()->after('status');
            $table->string('transfer_reference', 150)->nullable()->after('rejection_reason');
            $table->string('proof_path')->nullable()->after('transfer_reference');
            $table->unsignedBigInteger('processed_by')->nullable()->after('processed_at');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->timestamp('balance_credited_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('balance_credited_at');
        });

        Schema::table('owner_payouts', function (Blueprint $table): void {
            $table->dropColumn(['bank_nama', 'nomor_rekening', 'nama_pemilik_rekening', 'rejection_reason', 'transfer_reference', 'proof_path', 'processed_by']);
        });
        // `status` stays a string: old code only writes values it already knows.
    }
};
