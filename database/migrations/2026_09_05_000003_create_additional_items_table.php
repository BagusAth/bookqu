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
        Schema::create('additional_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('stock')->nullable();
            $table->boolean('is_unlimited')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['idtenant', 'is_active']);
        });

        Schema::create('additional_item_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idadditional_item')->constrained('additional_items')->cascadeOnDelete();
            $table->foreignId('idservice')->constrained('services')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['idadditional_item', 'idservice']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_item_service');
        Schema::dropIfExists('additional_items');
    }
};
