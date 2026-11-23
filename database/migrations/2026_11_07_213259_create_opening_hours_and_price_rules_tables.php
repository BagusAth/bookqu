<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-133 (FR-SCHEDULE-011, FR-SCHEDULE-012): weekly opening hours with breaks, how far ahead
 * slots may be opened, peak-hour rules and special-date prices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->unsignedSmallInteger('slot_open_days_ahead')->nullable();
        });

        Schema::create('opening_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->timestamps();
            $table->index(['idtenant', 'day_of_week']);
        });

        Schema::create('peak_hour_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->json('days');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->decimal('extra_amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('peak_hour_rule_service', function (Blueprint $table): void {
            $table->foreignId('peak_hour_rule_id')->constrained('peak_hour_rules')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->primary(['peak_hour_rule_id', 'service_id']);
        });

        Schema::create('special_date_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('price', 12, 2);
            $table->timestamps();
            $table->unique(['idtenant', 'service_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_date_prices');
        Schema::dropIfExists('peak_hour_rule_service');
        Schema::dropIfExists('peak_hour_rules');
        Schema::dropIfExists('opening_hours');
        Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn('slot_open_days_ahead'));
    }
};
