<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-154 (D-005, AC-HBK-04): the result of the DNS check of a business's own domain. A domain
 * resolves to the storefront only while its status is `connected`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('custom_domain_status', 16)->nullable()->after('custom_domain');
            $table->timestamp('custom_domain_checked_at')->nullable()->after('custom_domain_status');
            $table->timestamp('custom_domain_verified_at')->nullable()->after('custom_domain_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['custom_domain_status', 'custom_domain_checked_at', 'custom_domain_verified_at']);
        });
    }
};
