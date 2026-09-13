<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Owner can set minimum hours before booking time to allow cancellation
            $table->unsignedInteger('cancel_before_hours')->default(24)->after('weekend_price_value');
            // Optional: minimum hours before booking time to allow reschedule
            $table->unsignedInteger('reschedule_before_hours')->default(24)->after('cancel_before_hours');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['cancel_before_hours', 'reschedule_before_hours']);
        });
    }
};
