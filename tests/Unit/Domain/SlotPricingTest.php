<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Booking\BookingPricing;
use App\Domain\Schedule\PriceRules;
use App\Domain\Schedule\SlotPricing;
use App\Models\Service;
use PHPUnit\Framework\TestCase;

/**
 * FR-SCHEDULE-012 rule order and ADR-0019 decision 1 rounding. 2026-11-02 is a Monday,
 * 2026-11-07 a Saturday.
 */
class SlotPricingTest extends TestCase
{
    private function service(int $id = 1, float $price = 100000): Service
    {
        $service = new Service(['harga' => $price]);
        $service->id = $id;

        return $service;
    }

    /**
     * @param  list<array{name: string, days: list<int>, starts_at: string, ends_at: string, extra: float, service_ids: list<int>}>  $peak
     * @param  array<string, float>  $special
     */
    private function rules(string $weekend = 'none', ?float $value = null, array $peak = [], array $special = []): PriceRules
    {
        return new PriceRules($weekend, $value, $peak, $special);
    }

    public function test_base_price_applies_on_a_weekday_without_rules(): void
    {
        $this->assertSame(100000, SlotPricing::price($this->service(), '2026-11-02', '10:00', null, $this->rules('multiplier', 1.5))['price']);
    }

    public function test_weekend_multiplier_and_fixed_rules_change_the_base_price(): void
    {
        $this->assertSame(150000, SlotPricing::price($this->service(), '2026-11-07', '10:00', null, $this->rules('multiplier', 1.5))['price']);
        $this->assertSame(175000, SlotPricing::price($this->service(), '2026-11-07', '10:00', null, $this->rules('fixed', 175000))['price']);
        $this->assertSame('weekend', SlotPricing::price($this->service(), '2026-11-07', '10:00', null, $this->rules('fixed', 175000))['source']);
    }

    public function test_slot_override_wins_over_every_rule(): void
    {
        $rules = $this->rules('multiplier', 2, [['name' => 'Sore', 'days' => [6], 'starts_at' => '09:00:00', 'ends_at' => '12:00:00', 'extra' => 5000.0, 'service_ids' => []]], ['1|2026-11-07' => 90000]);

        $price = SlotPricing::price($this->service(), '2026-11-07', '10:00', '80000', $rules);

        $this->assertSame(80000, $price['price']);
        $this->assertSame('override', $price['source']);
    }

    public function test_special_date_price_replaces_base_and_weekend_and_peak_is_added(): void
    {
        $rules = $this->rules('multiplier', 2, [['name' => 'Pagi', 'days' => [6], 'starts_at' => '09:00:00', 'ends_at' => '12:00:00', 'extra' => 5000.0, 'service_ids' => []]], ['1|2026-11-07' => 90000]);

        $price = SlotPricing::price($this->service(), '2026-11-07', '10:00', null, $rules);

        $this->assertSame(95000, $price['price']);
        $this->assertSame('special_date', $price['source']);
        $this->assertSame([['name' => 'Pagi', 'extra' => 5000.0]], $price['peak']);
    }

    public function test_peak_rule_matches_weekday_window_and_services_only(): void
    {
        $rules = $this->rules(peak: [['name' => 'Malam', 'days' => [1], 'starts_at' => '18:00:00', 'ends_at' => '21:00:00', 'extra' => 20000.0, 'service_ids' => [2]]]);

        $this->assertSame(120000, SlotPricing::price($this->service(2), '2026-11-02', '18:00', null, $rules)['price']);
        $this->assertSame(100000, SlotPricing::price($this->service(2), '2026-11-02', '21:00', null, $rules)['price'], 'the end of the window is exclusive');
        $this->assertSame(100000, SlotPricing::price($this->service(2), '2026-11-10', '18:00', null, $rules)['price'], 'another weekday');
        $this->assertSame(100000, SlotPricing::price($this->service(1), '2026-11-02', '18:00', null, $rules)['price'], 'another service');
    }

    public function test_price_is_rounded_to_whole_rupiah_and_never_negative(): void
    {
        $this->assertSame(123457, SlotPricing::price($this->service(1, 98765.5), '2026-11-07', '10:00', null, $this->rules('multiplier', 1.25))['price']);
        $this->assertSame(0, SlotPricing::price($this->service(), '2026-11-02', '10:00', '-5', $this->rules())['price']);
    }

    public function test_quote_sums_slot_prices_per_participant_and_keeps_zero_overrides(): void
    {
        $slots = [
            (object) ['id' => 10, 'tanggal' => '2026-11-07', 'jam_mulai' => '09:00:00', 'harga_override' => null],
            (object) ['id' => 11, 'tanggal' => '2026-11-07', 'jam_mulai' => '10:00:00', 'harga_override' => 125000],
            (object) ['id' => 12, 'tanggal' => '2026-11-07', 'jam_mulai' => '11:00:00', 'harga_override' => 0],
        ];

        $quote = BookingPricing::quote($this->service(), $slots, $this->rules('multiplier', 1.5), 2);

        $this->assertSame([150000, 125000, 0], array_column($quote['lines'], 'unit_price'));
        $this->assertSame([300000, 250000, 0], array_column($quote['lines'], 'amount'));
        $this->assertSame(550000, $quote['base_subtotal']);
        $this->assertSame(550000, $quote['total']);
        $this->assertSame([], $quote['add_ons']);
        $this->assertNull($quote['promo']);
    }
}
