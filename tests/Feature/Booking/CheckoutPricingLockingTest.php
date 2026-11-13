<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\QuoteBooking;
use App\Actions\Booking\StorefrontCheckoutState;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\AdditionalItem;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Voucher;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('mysql')]
class CheckoutPricingLockingTest extends TestCase
{
    use DatabaseTruncation;

    public function test_last_promo_and_item_are_serialized_by_the_tenant_lock(): void
    {
        $this->beforeApplicationDestroyed(fn () => $this->truncateTablesForAllConnections());
        Notification::fake();
        $this->travelTo(now('Asia/Jakarta')->setTime(8, 0));
        config([
            'modules.promos' => 'released',
            'modules.add-ons' => 'released',
            'database.connections.pricing_concurrent' => config('database.connections.mysql'),
        ]);
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenantId($tenant->id);
        app(CreateTrialSubscription::class)->execute($tenant);
        $service = Service::factory()->for($tenant, 'tenant')->create(['harga' => 100000, 'is_active' => true]);
        $date = now()->addDay()->toDateString();
        $slots = collect(['10', '12'])->map(fn (string $hour) => Schedule::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'tanggal' => $date,
            'jam_mulai' => $hour.':00', 'jam_selesai' => $hour.':59', 'status' => 'tersedia', 'harga_override' => null,
        ]));
        Voucher::create(['idtenant' => $tenant->id, 'code' => 'LAST', 'discount_type' => 'fixed',
            'discount_value' => 100000, 'usage_limit' => 1, 'is_active' => true]);
        $item = AdditionalItem::create(['idtenant' => $tenant->id, 'name' => 'Item terakhir', 'price' => 0,
            'stock' => 1, 'is_unlimited' => false, 'is_active' => true]);
        $item->services()->attach($service->id);
        $addOns = [['id' => $item->id, 'quantity' => 1]];
        $place = function (Schedule $slot, ?string $code, array $selection) use ($tenant, $service, $date): array {
            $fingerprint = null;
            if ($slot->jam_mulai === '10:00:00' || $slot->jam_mulai === '10:00') {
                $quote = app(QuoteBooking::class)->execute($tenant, $service, $date, [$slot], 1, $code, $selection);
                $fingerprint = app(StorefrontCheckoutState::class)->fingerprintOfQuote($service, $date, $quote);
            }

            return app(CreateBooking::class)->execute($tenant, $service, $date, [substr($slot->jam_mulai, 0, 5)], [$slot->id],
                ['namapelanggan' => 'Pelanggan', 'nomorhp' => '081234567890', 'email' => 'uji@example.com', 'catatan' => null],
                1, $code, $selection, $fingerprint);
        };

        $other = DB::connection('pricing_concurrent');
        $other->beginTransaction();
        $other->table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        try {
            $place($slots[1], 'LAST', $addOns);
            $this->fail('Placement did not wait for the tenant lock.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Lock wait timeout exceeded', $exception->getMessage());
            $this->assertMatchesRegularExpression('/^select .* from `tenants` .* for update$/', $exception->getSql());
        } finally {
            $other->rollBack();
            DB::purge('pricing_concurrent');
            DB::statement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
        }

        $this->assertTrue($place($slots[0], 'LAST', $addOns)['free']);
        foreach ([['LAST', [], 'voucher_code'], [null, $addOns, 'selected_addons']] as [$code, $selection, $field]) {
            try {
                $place($slots[1], $code, $selection);
                $this->fail('Placement exceeded the remaining quota or stock.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($field, $exception->errors());
            }
        }
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('vouchers', ['idtenant' => $tenant->id, 'used_count' => 1]);
        $this->assertDatabaseHas('additional_items', ['id' => $item->id, 'stock' => 0]);
    }
}
