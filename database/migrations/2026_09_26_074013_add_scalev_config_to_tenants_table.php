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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('scalev_environment')->default('sandbox')->nullable()->after('midtrans_sandbox_server_key');
            $table->string('scalev_api_key')->nullable()->after('scalev_environment');
            $table->string('scalev_secret_key')->nullable()->after('scalev_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['scalev_environment', 'scalev_api_key', 'scalev_secret_key']);
        });
    }
};
