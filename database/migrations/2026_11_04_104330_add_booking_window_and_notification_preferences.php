<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->unsignedSmallInteger('booking_min_lead_hours')->default(0);
            $table->unsignedSmallInteger('booking_max_days_ahead')->nullable();
            $table->unsignedSmallInteger('booking_buffer_minutes')->default(0);
        });
        Schema::table('users', fn (Blueprint $table) => $table->json('notification_preferences')->nullable());
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('notification_preferences'));
        Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn([
            'booking_min_lead_hours', 'booking_max_days_ahead', 'booking_buffer_minutes',
        ]));
    }
};
