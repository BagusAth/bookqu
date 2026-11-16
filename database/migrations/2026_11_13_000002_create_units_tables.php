<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Units shared by services (ADR-0017 decision 4, FR-UNIT-001/002). Existing `resources` rows
 * and their service links become units; the old tables stay for the hidden staff-resources
 * module until BQ-157 removes it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idtenant')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 100);
            $table->unsignedInteger('kapasitas')->default(1);
            $table->string('lokasi', 255)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['idtenant', 'is_active']);
        });

        Schema::create('service_unit', function (Blueprint $table) {
            $table->foreignId('idservice')->constrained('services')->cascadeOnDelete();
            $table->foreignId('idunit')->constrained('units')->cascadeOnDelete();

            $table->primary(['idservice', 'idunit']);
            $table->index('idunit');
        });

        $unitOf = [];
        foreach (DB::table('resources')->orderBy('id')->get() as $resource) {
            $capacity = (int) preg_replace('/\D/', '', (string) $resource->capacity);
            $unitOf[$resource->id] = DB::table('units')->insertGetId([
                'idtenant' => $resource->idtenant,
                'name' => mb_substr((string) $resource->name, 0, 100),
                'type' => mb_substr((string) $resource->type, 0, 100),
                'kapasitas' => min(9999, max(1, $capacity)),
                'is_active' => (bool) $resource->is_active,
                'created_at' => $resource->created_at,
                'updated_at' => $resource->updated_at,
            ]);
        }

        foreach (DB::table('service_resource')->get() as $link) {
            if (isset($unitOf[$link->idresource])) {
                DB::table('service_unit')->insertOrIgnore(['idservice' => $link->idservice, 'idunit' => $unitOf[$link->idresource]]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_unit');
        Schema::dropIfExists('units');
    }
};
