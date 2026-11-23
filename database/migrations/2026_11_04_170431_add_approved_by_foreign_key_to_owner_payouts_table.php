<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BQ-127: expand step that links owner_payouts.approved_by to users.id. Approvers that no
 * longer exist are cleared first so the constraint can be created.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('owner_payouts')
            ->whereNotNull('approved_by')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('users')->whereColumn('users.id', 'owner_payouts.approved_by');
            })
            ->update(['approved_by' => null]);

        Schema::table('owner_payouts', function (Blueprint $table): void {
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('owner_payouts', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
        });
    }
};
