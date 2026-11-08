<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

use App\Models\PeakHourRule;
use App\Models\SpecialDatePrice;
use App\Models\Tenant;
use Carbon\Carbon;

/**
 * A tenant's price rules as plain data (FR-SCHEDULE-012): the weekend rule, peak-hour rules and
 * special-date prices. Loaded once per request and handed to SlotPricing, which owns the order.
 */
final class PriceRules
{
    /**
     * @param  list<array{name: string, days: list<int>, starts_at: string, ends_at: string, extra: float, service_ids: list<int>}>  $peakRules
     * @param  array<string, float>  $specialPrices  keyed by "{serviceId}|{Y-m-d}"
     */
    public function __construct(
        public readonly string $weekendType = 'none',
        public readonly ?float $weekendValue = null,
        public readonly array $peakRules = [],
        public readonly array $specialPrices = [],
    ) {}

    public static function none(): self
    {
        return new self;
    }

    /**
     * Load the tenant's rules. Special-date prices are limited to the given date range
     * (default: today onward), so the set stays small.
     */
    public static function forTenant(Tenant $tenant, ?string $fromDate = null, ?string $toDate = null): self
    {
        $from = $fromDate !== null ? Carbon::parse($fromDate)->toDateString() : Carbon::today('Asia/Jakarta')->toDateString();

        // scope-bypass: filtered by the given tenant
        $peakRules = PeakHourRule::withoutGlobalScopes()->where('idtenant', $tenant->id)->with('services:id')->orderBy('id')->get()
            ->map(fn (PeakHourRule $rule): array => [
                'name' => (string) $rule->name,
                'days' => array_map('intval', (array) $rule->days),
                'starts_at' => self::time((string) $rule->starts_at),
                'ends_at' => self::time((string) $rule->ends_at),
                'extra' => (float) $rule->extra_amount,
                'service_ids' => $rule->services->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            ])->values()->all();

        // scope-bypass: filtered by the given tenant
        $special = SpecialDatePrice::withoutGlobalScopes()->where('idtenant', $tenant->id)->whereDate('tanggal', '>=', $from);
        if ($toDate !== null) {
            $special->whereDate('tanggal', '<=', Carbon::parse($toDate)->toDateString());
        }
        $specialPrices = [];
        foreach ($special->get(['service_id', 'tanggal', 'price']) as $row) {
            $specialPrices[(int) $row->service_id.'|'.$row->tanggal->toDateString()] = (float) $row->price;
        }

        $weekendType = in_array($tenant->weekend_price_type, ['multiplier', 'fixed'], true) ? (string) $tenant->weekend_price_type : 'none';

        return new self(
            $weekendType,
            $tenant->weekend_price_value !== null ? (float) $tenant->weekend_price_value : null,
            $peakRules,
            $specialPrices,
        );
    }

    public function specialPrice(int $serviceId, string $date): ?float
    {
        return $this->specialPrices[$serviceId.'|'.$date] ?? null;
    }

    /**
     * Normalise "H:i" or "H:i:s" to "H:i:s".
     */
    public static function time(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : substr($time, 0, 8);
    }
}
