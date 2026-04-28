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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('idtenant');
            $table->bigInteger('idplan');
            $table->enum('status', ['trial', 'active', 'expired', 'cancelled']);
            $table->dateTime('trial_berakhir')->nullable();
            $table->dateTime('langganan_mulai')->nullable();
            $table->dateTime('langganan_berakhir')->nullable();
            $table->timestamps();

            $table->foreign('idtenant')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('idplan')->references('id')->on('plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
