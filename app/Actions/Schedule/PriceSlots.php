<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Schedule\PriceRules;
use App\Domain\Schedule\SlotPricing;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\Rupiah;
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
        return array_map(fn (array $price): int => $price['price'], $this->explain($tenant, $schedules));
    }

    /**
     * Price per slot with a short note on what changed it (BQ-162, Slot tab): the peak-hour
     * extras ("Malam kerja +Rp50.000"), the weekend rule, a special date or a per-slot price.
     *
     * @param  iterable<Schedule>  $schedules  slots of this tenant, with `layanan` loaded
     * @return array<int, array{price: int, note: ?string}> by schedule id
     */
    public function explain(Tenant $tenant, iterable $schedules): array
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
            $price = SlotPricing::price(
                $service,
                Carbon::parse($schedule->tanggal)->toDateString(),
                (string) $schedule->jam_mulai,
                $schedule->harga_override,
                $rules,
            );
            $notes = match ($price['source']) {
                'override' => ['Harga khusus slot'],
                'special_date' => ['Tanggal khusus'],
                'weekend' => ['Akhir pekan'],
                default => [],
            };
            foreach ($price['peak'] as $peak) {
                $notes[] = $peak['name'].' +'.Rupiah::format($peak['extra']);
            }
            $prices[(int) $schedule->id] = ['price' => (int) $price['price'], 'note' => $notes === [] ? null : implode(' · ', $notes)];
        }

        return $prices;
    }
}
