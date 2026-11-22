<?php

namespace Tests\Feature\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** BQ-162 batch 4: quiet-hour promo limits (FR-VOUCHER-006). */
class QuietHourPromoTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_the_promo_card_shows_the_days_and_hours_it_is_limited_to(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::factory()->create(['iduser' => $owner->id]);
        app(CreateTrialSubscription::class)->execute($tenant);
        $this->createPromo($tenant, 'SEPI20', ['valid_days' => '1,2', 'valid_from_time' => '10:00:00', 'valid_until_time' => '12:00:00']);
        $this->createPromo($tenant, 'BEBAS10', []);

        $response = $this->actingAs($owner)->get(route('owner.promos'));

        $response->assertOk()->assertSee('Hanya Sen, Sel · 10.00–12.00');
        $this->assertSame(1, substr_count((string) $response->getContent(), 'data-promo-limits'));
    }

    /** @param  array<string, string>  $limits */
    private function createPromo(Tenant $tenant, string $code, array $limits): Voucher
    {
        return Voucher::create($limits + [
            'idtenant' => $tenant->id, 'code' => $code, 'discount_type' => 'percentage', 'discount_value' => 20,
            'min_order_amount' => 0, 'usage_limit' => 0, 'applicable_services' => 'all', 'is_active' => true,
        ]);
    }
}
