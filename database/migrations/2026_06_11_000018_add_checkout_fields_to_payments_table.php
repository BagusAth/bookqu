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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('order_id', 50)->nullable()->unique()->after('external_id');
            $table->string('snap_token', 255)->nullable()->after('order_id');
            $table->dateTime('expired_at')->nullable()->after('snap_token');
            $table->string('nama_pembayar', 100)->nullable()->after('expired_at');
            $table->string('email_pembayar', 100)->nullable()->after('nama_pembayar');
            $table->string('hp_pembayar', 20)->nullable()->after('email_pembayar');
            $table->text('catatan')->nullable()->after('hp_pembayar');
            $table->foreignId('idplan')->nullable()->after('idtenant');

            $table->foreign('idplan')->references('id')->on('plans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['idplan']);
            $table->dropColumn([
                'order_id',
                'snap_token',
                'expired_at',
                'nama_pembayar',
                'email_pembayar',
                'hp_pembayar',
                'catatan',
                'idplan',
            ]);
        });
    }
};
