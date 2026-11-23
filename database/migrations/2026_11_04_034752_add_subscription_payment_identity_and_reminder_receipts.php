<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('source_payment_id')->nullable()->unique()->constrained('payments')->nullOnDelete();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('trial_used_at')->nullable();
        });
        $ownerIds = DB::table('subscriptions')->join('tenants', 'tenants.id', '=', 'subscriptions.idtenant')->whereNotNull('trial_berakhir')->distinct()->pluck('tenants.iduser');
        DB::table('users')->whereIn('id', $ownerIds)->update(['trial_used_at' => now()]);
        Schema::create('subscription_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->timestamp('period_end_at');
            $table->unsignedTinyInteger('days_before');
            $table->timestamps();
            $table->unique(['subscription_id', 'period_end_at', 'days_before'], 'subscription_reminder_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_reminders');
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropUnique('subscriptions_source_payment_id_unique');
            $table->dropConstrainedForeignId('source_payment_id');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('trial_used_at');
        });
    }
};
