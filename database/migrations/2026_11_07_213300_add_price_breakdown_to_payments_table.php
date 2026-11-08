<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-148 (FR-BOOKING-023, ADR-0019 decision 2): the frozen price breakdown of a booking group,
 * stored on its payment. Null for subscription payments and bookings placed before this change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->json('price_breakdown')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('price_breakdown'));
    }
};
