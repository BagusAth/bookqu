<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-081: a walk-in customer may have no e-mail. Validation and every mail path already
 * treat the address as optional; only the column still refused NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('email', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('bookings')->whereNull('email')->update(['email' => '']);

        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('email', 100)->nullable(false)->change();
        });
    }
};
