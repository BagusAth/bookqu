<?php

namespace Tests\Feature\Owner;

use App\Models\Voucher;
use Tests\TestCase;

/** BQ-162 batch 4: quiet-hour promo limits (FR-VOUCHER-006). */
class QuietHourPromoTest extends TestCase
{
    public function test_a_quiet_hour_promo_covers_only_its_days_and_hours(): void
    {
        // Tuesday to Thursday, 13:00 until 17:00.
        $promo = new Voucher(['valid_days' => '2,3,4', 'valid_from_time' => '13:00:00', 'valid_until_time' => '17:00:00']);

        $this->assertTrue($promo->hasQuietHours());
        $this->assertTrue($promo->coversSlot('2026-11-18', '13:00:00'));
        $this->assertTrue($promo->coversSlot('2026-11-19', '16:00'));
        $this->assertFalse($promo->coversSlot('2026-11-19', '17:00'));
        $this->assertFalse($promo->coversSlot('2026-11-18', '12:00'));
        $this->assertFalse($promo->coversSlot('2026-11-21', '14:00'));
    }

    public function test_a_promo_without_limits_covers_every_slot(): void
    {
        $promo = new Voucher;

        $this->assertFalse($promo->hasQuietHours());
        $this->assertTrue($promo->coversSlot('2026-11-22', '06:00'));
    }
}
