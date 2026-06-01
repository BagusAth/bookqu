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
        Schema::create('owner_blocked_dates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('idtenant');
            $table->date('tanggal');
            $table->string('alasan', 200)->nullable();
            $table->timestamps();

            $table->foreign('idtenant')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['idtenant', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_blocked_dates');
    }
};
