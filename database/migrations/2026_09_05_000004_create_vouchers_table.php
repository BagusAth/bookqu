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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 50);
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('min_spending', 12, 2)->default(0);
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('applicable_services', 255)->nullable()->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['idtenant', 'code']);
            $table->index(['idtenant', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
