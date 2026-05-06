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
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('deskripsi');
            $table->boolean('is_popular')->default(false)->after('is_active');
            $table->string('image_url')->nullable()->after('is_popular');
            $table->unsignedInteger('kapasitas')->nullable()->after('image_url');
            $table->string('satuan_harga', 20)->default('sesi')->after('kapasitas');
            $table->string('satuan_durasi', 20)->default('menit')->after('satuan_harga');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'is_popular',
                'image_url',
                'kapasitas',
                'satuan_harga',
                'satuan_durasi',
            ]);
        });
    }
};
