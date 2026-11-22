<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $cleared = DB::table('owner_payouts')
            ->whereNotNull('processed_by')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('users')->whereColumn('users.id', 'owner_payouts.processed_by');
            })
            ->update(['processed_by' => null]);
        Log::info('Cleared orphaned payout processor references.', ['count' => $cleared]);

        Schema::table('owner_payouts', function (Blueprint $table): void {
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_payouts', function (Blueprint $table): void {
            $table->dropForeign(['processed_by']);
        });
    }
};
