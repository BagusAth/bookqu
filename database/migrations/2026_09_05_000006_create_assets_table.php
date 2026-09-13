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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('category', 50)->default('general'); // logo, cover, service, gallery, general
            $table->string('file_path', 255);
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->string('dimensions', 50)->nullable(); // e.g. 1920x1080
            $table->string('mime_type', 50)->nullable();
            $table->timestamps();

            $table->index(['idtenant', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
