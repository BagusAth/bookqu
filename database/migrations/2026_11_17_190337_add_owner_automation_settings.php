<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-162 batch 4 (D-026): automatic completion is on by default per tenant (FR-BOOKING-024);
 * automatic slot opening is off by default per service (FR-SCHEDULE-015).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->boolean('auto_complete_bookings')->default(true);
        });
        Schema::table('services', function (Blueprint $table): void {
            $table->boolean('auto_open_slots')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('auto_open_slots'));
        Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn('auto_complete_bookings'));
    }
};
