<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->unsignedInteger('customer_access_version')->default(1);
        });
        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedInteger('customer_access_version')->default(1);
        });
        Schema::create('customer_access_grants', function (Blueprint $table): void {
            $table->id();
            $table->string('selector', 32)->unique();
            $table->string('secret_hash', 64);
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->string('profile', 20);
            $table->foreignId('payment_id')->nullable()->constrained('payments')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->cascadeOnDelete();
            $table->json('booking_ids');
            $table->unsignedInteger('family_version');
            $table->text('recipient')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_access_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('source_grant_id')->nullable()->constrained('customer_access_grants')->cascadeOnDelete();
            $table->json('booking_ids');
            $table->unsignedInteger('family_version');
            $table->text('recipient');
            $table->string('reason', 40);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_access_deliveries');
        Schema::dropIfExists('customer_access_grants');
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('customer_access_version'));
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('customer_access_version'));
    }
};
