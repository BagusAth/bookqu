<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Virtual column that holds the schedule ID only if the booking is active
            $table->unsignedBigInteger('active_idschedule')
                  ->nullable()
                  ->virtualAs('CASE WHEN status IN (\'pending\', \'paid\', \'completed\') THEN idschedule ELSE NULL END');
            
            // Unique constraint prevents two active bookings for the same schedule slot
            $table->unique(['active_idschedule'], 'unique_active_booking_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('unique_active_booking_slot');
            $table->dropColumn('active_idschedule');
        });
    }
};
