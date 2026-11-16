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
        Schema::create('owner_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idtenant')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->text('google_access_token')->nullable();
            $table->text('google_refresh_token')->nullable();
            $table->timestamp('google_expires_at')->nullable();
            $table->string('google_analytics_id', 24)->nullable();
            $table->string('meta_pixel_id', 24)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_integrations');
    }
};
