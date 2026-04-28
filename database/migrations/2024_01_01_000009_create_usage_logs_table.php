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
        Schema::create('usage_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('idtenant');
            $table->enum('jenis', ['booking', 'layanan']);
            $table->integer('jumlah');
            $table->date('periode');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('idtenant')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_logs');
    }
};
