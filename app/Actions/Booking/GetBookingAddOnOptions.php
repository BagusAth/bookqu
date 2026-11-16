<?php

namespace App\Actions\Booking;

use App\Domain\Modules\ModuleRelease;
use App\Domain\Subscription\EntitlementRules;
use App\Models\AdditionalItem;
use App\Models\Tenant;

final class GetBookingAddOnOptions
{
    /**
     * @param  list<int>  $serviceIds
     * @return array<int, list<array{id: int, name: string}>>
     */
    public function execute(Tenant $tenant, array $serviceIds): array
    {
        if ($serviceIds === [] || ModuleRelease::state('add-ons') !== 'released'
            || ! EntitlementRules::canAccessFeature($tenant->subscription()->with('plan')->first(), 'add-ons')['allowed']) {
            return [];
        }

        $items = AdditionalItem::where('idtenant', $tenant->id)->where('is_active', true)
            ->where(fn ($query) => $query->where('is_unlimited', true)->orWhere('stock', '>', 0))
            ->with(['services' => fn ($query) => $query->where('services.idtenant', $tenant->id)->whereIn('services.id', $serviceIds)])
            ->orderBy('name')->get();

        $options = [];
        foreach ($items as $item) {
            foreach ($item->services as $service) {
                $options[(int) $service->getKey()][] = ['id' => $item->id, 'name' => $item->name];
            }
        }

        return $options;
    }
}
