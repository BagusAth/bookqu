<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A refund owed for a subscription payment that succeeded late has no booking
 * (ADR-0004 decisions 5 and 7). Expand-only: existing rows keep their booking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable(false)->change();
        });
    }
};
