<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-157: the withdrawn staff-resources module is removed. Its rows were copied to `units`
 * by 2026_11_13_000002_create_units_tables (ADR-0017), so the old tables are dropped.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('service_resource');
        Schema::dropIfExists('resources');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('type', 100);
            $table->string('capacity', 100)->nullable();
            $table->string('availability_status', 100)->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['idtenant', 'is_active']);
        });

        Schema::create('service_resource', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idservice')->constrained('services')->cascadeOnDelete();
            $table->foreignId('idresource')->constrained('resources')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['idservice', 'idresource']);
        });
    }
};
