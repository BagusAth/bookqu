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
            $table->text('deskripsi')->nullable()->after('alamat');
            $table->string('logo_path', 255)->nullable()->after('deskripsi');

            $table->enum('payment_mode', ['platform', 'owner'])->default('platform')->after('logo_path');
            $table->enum('midtrans_status', ['pending', 'approved', 'rejected'])->default('pending')->after('payment_mode');
            $table->enum('midtrans_environment', ['sandbox', 'production'])->default('sandbox')->after('midtrans_status');
            $table->string('midtrans_sandbox_merchant_id', 100)->nullable()->after('midtrans_environment');
            $table->string('midtrans_sandbox_client_key', 200)->nullable()->after('midtrans_sandbox_merchant_id');
            $table->string('midtrans_sandbox_server_key', 200)->nullable()->after('midtrans_sandbox_client_key');
            $table->string('midtrans_prod_merchant_id', 100)->nullable()->after('midtrans_sandbox_server_key');
            $table->string('midtrans_prod_client_key', 200)->nullable()->after('midtrans_prod_merchant_id');
            $table->string('midtrans_prod_server_key', 200)->nullable()->after('midtrans_prod_client_key');

            $table->decimal('saldo_platform', 14, 2)->default(0)->after('midtrans_prod_server_key');

            $table->enum('weekend_price_type', ['none', 'multiplier', 'fixed'])->default('none')->after('saldo_platform');
            $table->decimal('weekend_price_value', 12, 2)->nullable()->after('weekend_price_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'deskripsi',
                'logo_path',
                'payment_mode',
                'midtrans_status',
                'midtrans_environment',
                'midtrans_sandbox_merchant_id',
                'midtrans_sandbox_client_key',
                'midtrans_sandbox_server_key',
                'midtrans_prod_merchant_id',
                'midtrans_prod_client_key',
                'midtrans_prod_server_key',
                'saldo_platform',
                'weekend_price_type',
                'weekend_price_value',
            ]);
        });
    }
};
