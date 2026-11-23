<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->boolean('refunds_enabled')->default(false);
        });
        Schema::create('refund_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('idpayment')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('reason', 500);
            $table->text('bank_nama');
            $table->text('nomor_rekening');
            $table->text('nama_pemilik_rekening');
            $table->string('status', 24)->default('open');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_reason', 500)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->date('last_reminded_on')->nullable();
            $table->timestamps();
            $table->index(['idpayment', 'status']);
            $table->index(['status', 'id']);
        });
        Schema::table('refunds', function (Blueprint $table): void {
            $table->foreignId('refund_request_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', fn (Blueprint $table) => $table->dropConstrainedForeignId('refund_request_id'));
        Schema::dropIfExists('refund_requests');
        Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn('refunds_enabled'));
    }
};
