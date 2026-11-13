<?php

namespace Tests\Unit;

use App\Domain\Booking\BookingPricing;
use App\Domain\Schedule\PriceRules;
use App\Models\Service;
use App\Models\Voucher;
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
}
