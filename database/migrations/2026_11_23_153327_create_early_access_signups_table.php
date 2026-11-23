<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Early access sign-ups from the public `/early-access` page (BQ-175, FR-PUBLIC-009).
 * Platform data: no tenant column. One sign-up per email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('early_access_signups', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 100);
            $table->string('business_name', 150);
            $table->string('business_type', 100);
            $table->string('email', 150)->unique();
            $table->string('whatsapp_number', 20);
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('early_access_signups');
    }
};
