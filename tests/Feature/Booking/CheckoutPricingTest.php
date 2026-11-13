<?php

namespace Tests\Feature\Booking;

use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Contracts\Payments\PaymentGatewayInterface;
use App\Domain\Payment\PaymentTransition;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Models\AdditionalItem;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutPricingTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('outcomes')]
    public function test_checkout_reserves_last_promo_and_item_until_terminal_payment(bool $paid): void
    {
        $this->travelTo(Carbon::parse('2026-11-13 10:00', 'Asia/Jakarta'));
        config(['modules.promos' => 'released', 'modules.add-ons' => 'released']);
        $tenant = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($tenant);
        $service = Service::factory()->create(['idtenant' => $tenant->id, 'harga' => 100000, 'is_active' => true, 'durasi' => 60]);
        $voucher = Voucher::create(['idtenant' => $tenant->id, 'code' => 'HEMAT', 'discount_type' => 'fixed',
            'discount_value' => 25000, 'max_discount' => 20000, 'usage_limit' => 1, 'is_active' => true]);
        $item = AdditionalItem::create(['idtenant' => $tenant->id, 'name' => 'Handuk', 'price' => 5000, 'stock' => 1, 'is_unlimited' => false, 'is_active' => true]);
        $item->services()->attach($service->id);
        $slot = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id, 'tanggal' => '2026-11-20',
            'jam_mulai' => '10:00', 'jam_selesai' => '10:59', 'status' => 'tersedia', 'harga_override' => null]);
        config(['payment.default' => 'midtrans']);
        $gateway = \Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('getDriverName')->andReturn('midtrans');
        $gateway->shouldReceive('createBookingPayment')->once()->withArgs(function (Payment $payment): bool {
            return (int) $payment->jumlah === 85000;
        })->andReturn(['snap_token' => 'test-snap']);
        app(PaymentGatewayManager::class)->extend('midtrans', fn () => $gateway);
        $this->withSession(['booking' => [
            'tenant_id' => $tenant->id, 'service_id' => $service->id, 'tanggal' => '2026-11-20',
            'jam' => ['10:00'], 'schedule_ids' => [$slot->id],
        ]]);
        $base = '/'.$tenant->slug.'/booking';
        $extras = ['voucher_code' => 'HEMAT', 'selected_addons' => [['id' => $item->id, 'quantity' => 1]]];
        $foreign = AdditionalItem::create(['idtenant' => Tenant::factory()->create()->id, 'name' => 'Asing', 'price' => 1, 'stock' => 10, 'is_active' => true]);
        $this->postJson($base.'/validate-voucher', ['selected_addons' => [['id' => $foreign->id, 'quantity' => 1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('selected_addons');
        $quote = $this->postJson($base.'/validate-voucher', $extras)->assertOk()->assertJsonPath('quote.total', 85000);
        $customer = ['namapelanggan' => 'Pelanggan Uji', 'email' => 'uji@example.test', 'nomorhp' => '08123456789',
            'checkout_fingerprint' => $quote->json('checkout_fingerprint')];
        $this->post($base.'/checkout', $extras + $customer)->assertRedirect()->assertSessionHasNoErrors();

        // scope-bypass: this test inspects the payment it created after HTTP clears tenant context.
        $payment = Payment::withoutGlobalScopes()->where('idtenant', $tenant->id)->firstOrFail();
        $this->assertSame(85000, (int) $payment->jumlah);
        // The stored breakdown equals the quote; MySQL JSON columns return object keys in their own order.
        $this->assertSame($this->sortedKeys($quote->json('quote')), $this->sortedKeys($payment->price_breakdown));
        $this->assertDatabaseHas('additional_items', ['id' => $item->id, 'stock' => 1]);
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'used_count' => 0]);

        $this->withSession(['booking' => [
            'tenant_id' => $tenant->id, 'service_id' => $service->id, 'tanggal' => '2026-11-20',
            'jam' => ['10:00'], 'schedule_ids' => [$slot->id],
        ]]);
        $this->postJson($base.'/validate-voucher', ['voucher_code' => 'HEMAT'])
            ->assertUnprocessable()->assertJsonValidationErrors('voucher_code');
        $this->postJson($base.'/validate-voucher', ['selected_addons' => $extras['selected_addons']])
            ->assertUnprocessable()->assertJsonValidationErrors('selected_addons');

        $lifecycle = app(PaymentLifecycle::class);
        $lifecycle->transition($payment, $paid ? PaymentTransition::ProviderSucceeded : PaymentTransition::Expired);
        $lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);
        $this->assertDatabaseCount('booking_pricing_reservations', 0);
        $this->assertDatabaseHas('additional_items', ['id' => $item->id, 'stock' => $paid ? 0 : 1]);
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'used_count' => $paid ? 1 : 0]);
    }

    public static function outcomes(): array
    {
        return ['duplicate success' => [true], 'expiry then late success' => [false]];
    }

    /**
     * The same data with every object's keys in one order; list order and values stay as they are.
     *
     * @param  array<array-key, mixed>|null  $data
     * @return array<array-key, mixed>|null
     */
    private function sortedKeys(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $data = array_map(fn (mixed $value): mixed => is_array($value) ? $this->sortedKeys($value) : $value, $data);
        if (! array_is_list($data)) {
            ksort($data);
        }

        return $data;
    }
}
