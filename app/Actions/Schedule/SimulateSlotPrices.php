<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Schedule\PriceRules;
use App\Domain\Schedule\SlotPricing;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;

/**
 * Price simulator (FR-SCHEDULE-012, AC-JBH-02): the price of each hour of a date for a service,
 * from the same rule the quote and the charge use. Hours follow the day's opening hours, or the
 * whole day when none are set.
 */
class SimulateSlotPrices
{
    /**
     * @return list<array{time: string, price: int, source: string, peak: list<array{name: string, extra: float}>}>
     */
    public function execute(Tenant $tenant, Service $service, string $date): array
    {
        if ((int) $service->idtenant !== (int) $tenant->id) {
            abort(404);
        }

        $day = Carbon::parse($date)->toDateString();
        $rules = PriceRules::forTenant($tenant, $day, $day);

        // scope-bypass: filtered by the authenticated tenant
        $intervals = OpeningHour::withoutGlobalScopes()->where('idtenant', $tenant->id)
            ->where('day_of_week', Carbon::parse($day)->dayOfWeekIso)->orderBy('opens_at')->get(['opens_at', 'closes_at']);

        $hours = [];
        if ($intervals->isEmpty()) {
            $hours = range(0, 23);
        } else {
            foreach ($intervals as $interval) {
                $hour = (int) substr((string) $interval->opens_at, 0, 2);
                $closes = PriceRules::time((string) $interval->closes_at);
                while (sprintf('%02d:00:00', $hour) < $closes && $hour < 24) {
                    $hours[] = $hour++;
                }
            }
        }

        $result = [];
        foreach (array_values(array_unique($hours)) as $hour) {
            $time = sprintf('%02d:00', $hour);
            $price = SlotPricing::price($service, $day, $time, null, $rules);
            $result[] = ['time' => $time, 'price' => $price['price'], 'source' => $price['source'], 'peak' => $price['peak']];
        }

        return $result;
    }
}
