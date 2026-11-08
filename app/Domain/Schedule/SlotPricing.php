<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

use App\Models\Service;
use Carbon\Carbon;

/**
 * The single home of a slot's price (FR-SCHEDULE-012). Order:
 *
 * 1. A per-slot override is the price; no rule changes it.
 * 2. Otherwise a special-date price for the service and date replaces the base price.
 * 3. Otherwise, on Saturday and Sunday, the weekend rule applies to the base price:
 *    `multiplier` multiplies it, `fixed` replaces it with the value.
 * 4. Every matching peak-hour rule (weekday, start time inside [from, to), service or all
 *    services) adds its extra amount to the result of 2 or 3.
 *
 * The price is never below zero and is rounded to whole rupiah, so the simulator, the quote and
 * the charge agree (AC-JBH-02, ADR-0019 decision 1).
 */
final class SlotPricing
{
    /**
     * @return array{price: int, source: 'override'|'special_date'|'weekend'|'base', base: float, peak: list<array{name: string, extra: float}>}
     */
    public static function price(Service $service, string $date, string $startTime, mixed $override, PriceRules $rules): array
    {
        $base = (float) $service->harga;

        if ($override !== null && $override !== '') {
            return ['price' => self::round((float) $override), 'source' => 'override', 'base' => $base, 'peak' => []];
        }

        $day = Carbon::parse($date);
        $dateString = $day->toDateString();
        $special = $rules->specialPrice((int) $service->id, $dateString);

        if ($special !== null) {
            $price = $special;
            $source = 'special_date';
        } else {
            $weekend = $day->isWeekend() ? self::weekendPrice($base, $rules->weekendType, $rules->weekendValue) : null;
            $price = $weekend ?? $base;
            $source = $weekend !== null ? 'weekend' : 'base';
        }

        $peak = [];
        $start = PriceRules::time($startTime);
        foreach ($rules->peakRules as $rule) {
            if (in_array($day->dayOfWeekIso, $rule['days'], true)
                && $start >= $rule['starts_at'] && $start < $rule['ends_at']
                && ($rule['service_ids'] === [] || in_array((int) $service->id, $rule['service_ids'], true))) {
                $peak[] = ['name' => $rule['name'], 'extra' => $rule['extra']];
                $price += $rule['extra'];
            }
        }

        return ['price' => self::round($price), 'source' => $source, 'base' => $base, 'peak' => $peak];
    }

    /**
     * Weekend price from the base price, or null when the rule does not change it.
     */
    public static function weekendPrice(float $base, ?string $type, ?float $value): ?float
    {
        if ($type === 'multiplier' && $value) {
            return $base * $value;
        }

        if ($type === 'fixed' && $value) {
            return $value;
        }

        return null;
    }

    private static function round(float $price): int
    {
        return (int) round(max(0.0, $price));
    }
}
