<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Unique booking code for customer-facing URL: BKQ-20260618-ABCD1234
            $table->string('booking_code', 30)->nullable()->unique()->after('id');

            // 64-char secure tokens for management access (no login required)
            $table->string('cancellation_token', 64)->nullable()->after('booking_code');
            $table->string('reschedule_token', 64)->nullable()->after('cancellation_token');

            // Reschedule history — store the original date/time before change
            $table->date('rescheduled_from_date')->nullable()->after('catatan');
            $table->time('rescheduled_from_time')->nullable()->after('rescheduled_from_date');
            $table->foreignId('rescheduled_from_schedule')->nullable()->after('rescheduled_from_time');

            // Index for fast token lookups
            $table->index('cancellation_token');
            $table->index('reschedule_token');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['cancellation_token']);
            $table->dropIndex(['reschedule_token']);
            $table->dropColumn([
                'booking_code',
                'cancellation_token',
                'reschedule_token',
                'rescheduled_from_date',
                'rescheduled_from_time',
                'rescheduled_from_schedule',
            ]);
        });
    }
};
