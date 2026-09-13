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
            $table->string('button_style', 50)->default('rounded-xl')->after('theme_color');
            $table->string('font_family', 100)->default('Plus Jakarta Sans')->after('button_style');
            $table->string('card_style', 50)->default('elevated')->after('font_family');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['button_style', 'font_family', 'card_style']);
        });
    }
};
