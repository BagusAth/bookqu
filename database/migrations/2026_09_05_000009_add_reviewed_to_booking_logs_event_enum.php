<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE booking_logs MODIFY COLUMN event ENUM(
                'created',
                'payment_pending',
                'payment_success',
                'payment_failed',
                'cancelled',
                'rescheduled',
                'viewed',
                'reviewed'
            ) NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE booking_logs MODIFY COLUMN event ENUM(
                'created',
                'payment_pending',
                'payment_success',
                'payment_failed',
                'cancelled',
                'rescheduled',
                'viewed'
            ) NOT NULL");
        }
    }
};
