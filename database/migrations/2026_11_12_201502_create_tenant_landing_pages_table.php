<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-154 (FR-PUBLIC-007): one landing page per business, published on the storefront home.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_landing_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('template', 32)->default('modern');
            $table->string('headline', 120)->nullable();
            $table->json('sections')->nullable();
            $table->foreignId('banner_asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_landing_pages');
    }
};
