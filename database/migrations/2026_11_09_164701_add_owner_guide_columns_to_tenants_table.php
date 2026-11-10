<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-136 (owner-portal.md §8.1 point 7, AC-BRD-05): the new owner guide records when the booking
 * link was first shared (step 4) and when all steps were done, so the guide never comes back.
 * Expand-only; approved by the Tech Lead through the developer on 2026-11-09.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->timestamp('booking_link_shared_at')->nullable();
            $table->timestamp('owner_guide_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['booking_link_shared_at', 'owner_guide_completed_at']);
        });
    }
};
