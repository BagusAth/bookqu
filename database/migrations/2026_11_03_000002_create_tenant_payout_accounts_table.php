<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-123: Tenant saved payout bank accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_payout_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->string('bank_kode', 20);
            $table->string('bank_nama', 100);
            $table->string('nomor_rekening', 30);
            $table->string('nama_pemilik_rekening', 150);
            $table->boolean('is_default')->default(false);
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['idtenant', 'bank_kode', 'nomor_rekening']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payout_accounts');
    }
};
