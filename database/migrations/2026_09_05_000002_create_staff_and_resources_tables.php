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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('role', 100)->nullable();
            $table->string('availability_schedule', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['idtenant', 'is_active']);
        });

        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('type', 100);
            $table->string('capacity', 100)->nullable();
            $table->string('availability_status', 100)->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['idtenant', 'is_active']);
        });

        Schema::create('service_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idservice')->constrained('services')->cascadeOnDelete();
            $table->foreignId('idstaff')->constrained('staff')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['idservice', 'idstaff']);
        });

        Schema::create('service_resource', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idservice')->constrained('services')->cascadeOnDelete();
            $table->foreignId('idresource')->constrained('resources')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['idservice', 'idresource']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_resource');
        Schema::dropIfExists('service_staff');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('staff');
    }
};
