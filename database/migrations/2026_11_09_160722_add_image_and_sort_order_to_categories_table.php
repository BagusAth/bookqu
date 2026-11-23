<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-132 (FR-CATEGORY-006): a category has an optional image and a display order that the
 * public page respects. Expand-only; approved by the Tech Lead through the developer on 2026-11-09.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('image_url')->nullable()->after('color');
            $table->unsignedInteger('sort_order')->default(0)->after('image_url');
            $table->index(['idtenant', 'sort_order'], 'categories_tenant_sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex('categories_tenant_sort_order');
            $table->dropColumn(['image_url', 'sort_order']);
        });
    }
};
