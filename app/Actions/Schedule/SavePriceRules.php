<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\PeakHourRule;
use App\Models\Service;
use App\Models\SpecialDatePrice;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes a tenant's peak-hour rules and special-date prices (FR-SCHEDULE-012); the weekend rule
 * is saved by UpdateScheduleAvailability. Each write takes the tenant lock, so a
 * placement that is pricing under the same lock never sees a half-written rule (ADR-0005).
 */
class SavePriceRules
{
    /**
     * @param  array{name: string, days: list<int>, starts_at: string, ends_at: string, extra_amount: float, service_ids?: list<int>}  $data
     */
    public function savePeakRule(Tenant $tenant, array $data, ?PeakHourRule $rule = null): PeakHourRule
    {
        if ($rule !== null && (int) $rule->idtenant !== (int) $tenant->id) {
            abort(404);
        }
        $serviceIds = $this->ownedServiceIds($tenant, $data['service_ids'] ?? []);

        return DB::transaction(function () use ($tenant, $data, $rule, $serviceIds): PeakHourRule {
            ClaimSlots::lockTenant((int) $tenant->id);
            $rule ??= new PeakHourRule(['idtenant' => $tenant->id]);
            $rule->fill([
                'idtenant' => $tenant->id,
                'name' => $data['name'],
                'days' => array_values(array_unique(array_map('intval', $data['days']))),
                'starts_at' => substr($data['starts_at'], 0, 5).':00',
                'ends_at' => substr($data['ends_at'], 0, 5).':00',
                'extra_amount' => $data['extra_amount'],
            ])->save();
            $rule->services()->sync($serviceIds);

            return $rule;
        });
    }

    public function deletePeakRule(Tenant $tenant, PeakHourRule $rule): void
    {
        if ((int) $rule->idtenant !== (int) $tenant->id) {
            abort(404);
        }
        DB::transaction(function () use ($tenant, $rule): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            $rule->delete();
        });
    }

    /**
     * One price per service on the date; saving again replaces it.
     *
     * @param  list<int>  $serviceIds
     */
    public function saveSpecialDate(Tenant $tenant, string $date, float $price, array $serviceIds): int
    {
        $serviceIds = $this->ownedServiceIds($tenant, $serviceIds);
        if ($serviceIds === []) {
            throw ValidationException::withMessages(['service_ids' => 'Pilih minimal satu layanan.']);
        }

        DB::transaction(function () use ($tenant, $date, $price, $serviceIds): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            foreach ($serviceIds as $serviceId) {
                // scope-bypass: filtered by the authenticated tenant
                SpecialDatePrice::withoutGlobalScopes()->updateOrCreate(
                    ['idtenant' => $tenant->id, 'service_id' => $serviceId, 'tanggal' => $date],
                    ['price' => $price],
                );
            }
        });

        return count($serviceIds);
    }

    public function deleteSpecialDate(Tenant $tenant, SpecialDatePrice $specialDate): void
    {
        if ((int) $specialDate->idtenant !== (int) $tenant->id) {
            abort(404);
        }
        DB::transaction(function () use ($tenant, $specialDate): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            $specialDate->delete();
        });
    }

    /**
     * @param  list<int>  $serviceIds
     * @return list<int>
     */
    private function ownedServiceIds(Tenant $tenant, array $serviceIds): array
    {
        $serviceIds = array_values(array_unique(array_map('intval', $serviceIds)));
        if ($serviceIds === []) {
            return [];
        }
        // scope-bypass: filtered by the authenticated tenant
        $owned = Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->whereIn('id', $serviceIds)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if (count($owned) !== count($serviceIds)) {
            throw ValidationException::withMessages(['service_ids' => 'Layanan tidak ditemukan.']);
        }

        return $owned;
    }
}
