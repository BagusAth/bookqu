<?php

declare(strict_types=1);

namespace App\Actions\Unit;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Subscription\EntitlementRules;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Sets the units a service uses from the Layanan form (FR-UNIT-002). Runs inside the caller's
 * transaction under the tenant lock, and refuses a unit whose services would then have
 * overlapping future bookings (ADR-0017 decision 5). Units are a Pro feature (AC-UNT-03).
 */
class AssignServiceUnits
{
    /**
     * @param  array<int, int|string>  $unitIds
     *
     * @throws ValidationException
     */
    public function execute(Tenant $tenant, Service $service, array $unitIds): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('AssignServiceUnits must run inside a transaction.');
        }
        if ((int) $service->idtenant !== (int) $tenant->id) {
            abort(404);
        }

        ClaimSlots::lockTenant((int) $tenant->id);
        $unitIds = array_values(array_unique(array_map('intval', $unitIds)));
        $current = $service->units()->pluck('units.id')->map(fn ($id): int => (int) $id)->all();
        sort($unitIds);
        sort($current);
        if ($unitIds === $current) {
            return;
        }

        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
        if ($unitIds !== [] && ! EntitlementRules::canAccessFeature($subscription, 'units')['allowed']) {
            throw ValidationException::withMessages(['unit_ids' => 'Unit bersama tersedia di paket Pro.']);
        }

        // scope-bypass: filtered by the authenticated tenant
        $units = Unit::withoutGlobalScopes()->where('idtenant', $tenant->id)->whereIn('id', $unitIds)->with('services:id')->get();
        if ($units->count() !== count($unitIds)) {
            abort(404);
        }

        foreach ($units as $unit) {
            $before = $unit->services->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
            if ($unit->is_active && ! in_array((int) $service->id, $before, true)) {
                SaveUnit::refuseNewConflicts($tenant, $before, [...$before, (int) $service->id]);
            }
        }

        $service->units()->sync($unitIds);
    }
}
