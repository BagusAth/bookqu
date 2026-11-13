<?php

declare(strict_types=1);

namespace App\Actions\Unit;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Booking\BookingRules;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates or edits a unit and the services that use it (FR-UNIT-001/002). Changing which
 * services share a unit takes the tenant lock and refuses a change that would make future
 * bookings overlap on the unit, listing them (ADR-0017 decision 5).
 */
class SaveUnit
{
    /**
     * @param  array{name: string, type: string, kapasitas: int, lokasi?: ?string, image_path?: ?string, is_active?: bool, service_ids?: list<int>}  $data
     *
     * @throws ValidationException
     */
    public function execute(Tenant $tenant, array $data, ?Unit $unit = null): Unit
    {
        if ($unit !== null && (int) $unit->idtenant !== (int) $tenant->id) {
            abort(404);
        }

        return DB::transaction(function () use ($tenant, $data, $unit): Unit {
            ClaimSlots::lockTenant((int) $tenant->id);

            $serviceIds = self::ownedServiceIds($tenant, $data['service_ids'] ?? []);
            $active = (bool) ($data['is_active'] ?? true);
            $before = $unit !== null && $unit->is_active ? $unit->services()->pluck('services.id')->map(fn ($id): int => (int) $id)->all() : [];
            self::refuseNewConflicts($tenant, $before, $active ? $serviceIds : []);

            $unit ??= new Unit(['idtenant' => $tenant->id]);
            $unit->fill([
                'name' => $data['name'],
                'type' => $data['type'],
                'kapasitas' => $data['kapasitas'],
                'lokasi' => $data['lokasi'] ?? null,
                'image_path' => $data['image_path'] ?? $unit->image_path,
                'is_active' => $active,
            ])->save();
            $unit->services()->sync($serviceIds);

            return $unit;
        });
    }

    /**
     * Refuse when the services sharing a unit after the change have overlapping future
     * bookings that did not overlap on it before. Existing overlaps are only reported.
     *
     * @param  list<int>  $before
     * @param  list<int>  $after
     *
     * @throws ValidationException
     */
    public static function refuseNewConflicts(Tenant $tenant, array $before, array $after): void
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $existing = array_map(fn (array $pair): string => implode('-', $pair), BookingRules::unitOverlaps((int) $tenant->id, $before, $today));
        $new = array_values(array_filter(
            BookingRules::unitOverlaps((int) $tenant->id, $after, $today),
            fn (array $pair): bool => ! in_array(implode('-', $pair), $existing, true),
        ));

        if ($new !== []) {
            $ids = implode(', ', array_unique(array_merge(...$new)));
            throw ValidationException::withMessages([
                'service_ids' => "Perubahan ini membuat booking bertabrakan di unit yang sama (booking #{$ids}).",
            ]);
        }
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return list<int>
     */
    private static function ownedServiceIds(Tenant $tenant, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        // scope-bypass: filtered by the authenticated tenant
        $owned = Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->whereIn('id', $ids)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if (count($owned) !== count($ids)) {
            abort(404);
        }

        return $owned;
    }
}
