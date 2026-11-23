<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_logs', function (Blueprint $table): void {
            $table->string('event', 40)->change();
        });

        Schema::create('tenant_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('role', 20);
            $table->char('selector', 32)->unique();
            $table->char('secret_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('created_at');
            $table->index(['idtenant', 'accepted_at', 'revoked_at', 'expires_at'], 'team_invitations_pending');
        });

        Schema::create('booking_cancellation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500);
            $table->string('status', 16)->default('pending');
            $table->timestamp('created_at');
            $table->timestamp('decided_at')->nullable();
            $table->index(['idtenant', 'status', 'booking_id'], 'tenant_booking_cancellations');
        });
        Schema::table('refunds', function (Blueprint $table): void {
            $table->string('cause', 32)->nullable();
        });

        Schema::create('tenant_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('iduser')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 20);
            $table->string('event');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamp('created_at');
            $table->index(['idtenant', 'id']);
        });
    }

    public function down(): void
    {
        // The wider booking log event column remains compatible with the previous release.
        // Narrowing it would discard the cancellation audit history.
        Schema::dropIfExists('tenant_activity_logs');
        Schema::dropIfExists('booking_cancellation_requests');
        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropColumn('cause');
        });
        Schema::dropIfExists('tenant_invitations');
    }
};
