<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `payments.provider` is the only ownership rule for provider outcomes (ADR-0007
 * decision 2). Legacy rows without it are backfilled with the same inference as
 * PaymentRules::providerOf(). Making the column NOT NULL is a later contract step.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')->whereNull('provider')->where('metode', 'singapay')->update(['provider' => 'singapay']);
        DB::table('payments')->whereNull('provider')->whereIn('metode', ['gratis', 'cash'])->update(['provider' => 'manual']);
        // Legacy code overwrote `metode` with the payment type (qris, va, …); only SingaPay ever
        // stored a hosted payment link, so the link is the reliable evidence (release audit F2).
        DB::table('payments')->whereNull('provider')->whereNotNull('payment_url')->where('payment_url', '!=', '')->update(['provider' => 'singapay']);
        DB::table('payments')->whereNull('provider')->update(['provider' => 'midtrans']);
    }

    public function down(): void
    {
        // Backfilled values are indistinguishable from recorded ones; nothing to undo.
    }
};
