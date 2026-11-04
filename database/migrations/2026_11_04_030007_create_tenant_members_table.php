<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('iduser')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20);
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['idtenant', 'iduser']);
        });
        DB::table('tenants')->select(['id', 'iduser'])->orderBy('id')->chunkById(100, function ($tenants): void {
            foreach ($tenants as $tenant) {
                DB::table('tenant_members')->insert(['idtenant' => $tenant->id, 'iduser' => $tenant->iduser, 'role' => 'pemilik', 'accepted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_members');
    }
};
