<?php

namespace Tests\Unit;

use App\Domain\Booking\BookingPricing;
use App\Domain\Schedule\PriceRules;
use App\Models\Service;
use App\Models\Voucher;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingPricingTest extends TestCase
{
    public function test_discount_is_capped_and_applied_to_slots_after_add_ons(): void
    {
        $service = new Service(['harga' => 100]);
        $service->id = 1;
        $voucher = new Voucher(['code' => 'HEMAT', 'discount_type' => 'percentage', 'discount_value' => 50,
            'max_discount' => 40, 'min_spending' => 210, 'is_active' => true, 'applicable_services' => '1']);
        $voucher->id = 1;

        $quote = BookingPricing::quote($service, [['id' => 2, 'tanggal' => '2026-11-20', 'jam_mulai' => '10:00']], PriceRules::none(), 2,
            [['id' => 3, 'name' => 'Handuk', 'quantity' => 2, 'price' => 5.25, 'unlimited' => false]], $voucher);

        $this->assertSame(200, $quote['base_subtotal']);
        $this->assertSame(11, $quote['add_ons'][0]['amount']);
        $this->assertSame(40, $quote['promo']['discount']);
        $this->assertSame(171, $quote['total']);
    }

    public function test_a_slot_outside_a_quiet_hour_promo_is_refused_with_its_days_and_hours(): void
    {
        $service = new Service(['harga' => 100]);
        $service->id = 1;
        $voucher = new Voucher(['code' => 'SEPI20', 'discount_type' => 'percentage', 'discount_value' => 20, 'is_active' => true,
            'applicable_services' => 'all', 'valid_days' => '1,2', 'valid_from_time' => '10:00:00', 'valid_until_time' => '12:00:00']);
        $voucher->id = 1;

        try {
            // Monday 23 Nov 2026 at 14:00 is outside 10.00–12.00.
            BookingPricing::quote($service, [['id' => 2, 'tanggal' => '2026-11-23', 'jam_mulai' => '14:00']], PriceRules::none(), 1, [], $voucher);
            $this->fail('The quote accepted a slot outside the promo hours.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Kode promo ini hanya berlaku Sen, Sel · 10.00–12.00. Pilih jadwal di dalam waktu itu.',
                $e->errors()['voucher_code'][0],
            );
        }
    }
}
