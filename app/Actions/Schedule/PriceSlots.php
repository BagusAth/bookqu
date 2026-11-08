<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Schedule\PriceRules;
use App\Domain\Schedule\SlotPricing;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;

/**
 * Current price of each listed slot for owner and storefront displays, with the tenant's rules
 * loaded once (FR-SCHEDULE-012). Booked amounts come from the frozen breakdown, not from here.
 */
class PriceSlots
{
    /**
     * @param  iterable<Schedule>  $schedules  slots of this tenant, with `layanan` loaded
     * @return array<int, int> price by schedule id
     */
    public function execute(Tenant $tenant, iterable $schedules): array
    {
        $schedules = collect($schedules);
        if ($schedules->isEmpty()) {
            return [];
        }

        $dates = $schedules->map(fn (Schedule $s): string => Carbon::parse($s->tanggal)->toDateString());
        $rules = PriceRules::forTenant($tenant, (string) $dates->min(), (string) $dates->max());
        $prices = [];

        foreach ($schedules as $schedule) {
            $service = $schedule->layanan;
            if (! $service instanceof Service) {
                continue;
            }
            $prices[(int) $schedule->id] = SlotPricing::price(
                $service,
                Carbon::parse($schedule->tanggal)->toDateString(),
                (string) $schedule->jam_mulai,
                $schedule->harga_override,
                $rules,
            )['price'];
        }

        return $prices;
    }
}
