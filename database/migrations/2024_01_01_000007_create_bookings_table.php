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
        Schema::create('bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('idtenant');
            $table->bigInteger('idlayanan');
            $table->bigInteger('idschedule');
            $table->string('namapelanggan', 150);
            $table->string('nomorhp', 20);
            $table->string('email', 100);
            $table->date('tanggalbooking');
            $table->time('jam');
            $table->enum('status', ['pending', 'paid', 'cancelled', 'completed']);
            $table->bigInteger('idpayment')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('idtenant')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('idlayanan')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('idschedule')->references('id')->on('schedules')->onDelete('cascade');
            $table->foreign('idpayment')->references('id')->on('payments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
