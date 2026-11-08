<?php

declare(strict_types=1);

namespace Tests\Feature\Schedule;

use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\CreateWalkInBooking;
use App\Actions\Booking\StorefrontCheckoutState;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Payment;
use App\Models\PeakHourRule;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\SpecialDatePrice;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * BQ-133 (FR-SCHEDULE-011, FR-SCHEDULE-012, FR-SCHEDULE-014, AC-JBH-01/02/04/05) and BQ-148
 * (FR-BOOKING-023): price rules, opening hours, bulk preview, runway and the frozen amount.
 * The clock is Monday 2026-11-02 08:00 WIB; 2026-11-07 is a Saturday.
 */
class PriceRulesAndOpeningHoursTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Tenant $tenant;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00', 'Asia/Jakarta'));
        Mail::fake();

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'weekend_price_type' => 'none']);
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id, 'is_active' => true, 'durasi' => 60, 'harga' => 100000]);
        app(CreateTrialSubscription::class)->execute($this->tenant);
    }

    private function slot(string $date, string $start, mixed $override = null, ?Service $service = null): Schedule
    {
        $service ??= $this->service;

        return Schedule::factory()->create([
            'idtenant' => $service->idtenant,
            'idlayanan' => $service->id,
            'tanggal' => $date,
            'jam_mulai' => $start.':00',
            'jam_selesai' => Carbon::parse($start)->addMinutes(59)->format('H:i:s'),
            'harga_override' => $override,
            'status' => 'tersedia',
        ]);
    }

    private function paidBookingOn(Schedule $slot): Booking
    {
        $payment = Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 100000]);

        return Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $slot->idlayanan,
            'idschedule' => $slot->id,
            'idpayment' => $payment->id,
            'tanggalbooking' => $slot->tanggal,
            'jam' => $slot->jam_mulai,
            'status' => 'paid',
        ]);
    }

    public function test_simulator_price_equals_the_charged_price_for_multiplier_and_fixed_weekend_rules(): void
    {
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);

        foreach ([['multiplier', 1.5, '2026-11-07', 150000], ['fixed', 175000, '2026-11-08', 175000]] as [$type, $value, $date, $expected]) {
            $this->tenant->update(['weekend_price_type' => $type, 'weekend_price_value' => $value]);
            $slot = $this->slot($date, '10:00');

            $hours = $this->actingAs($this->owner)
                ->getJson(route('owner.schedule.price-simulator', ['idlayanan' => $this->service->id, 'tanggal' => $date]))
                ->assertOk()->json('hours');
            $simulated = collect($hours)->firstWhere('time', '10:00')['price'];

            app(TenantContext::class)->setTenantId((int) $this->tenant->id);
            $booking = app(CreateWalkInBooking::class)->execute($this->tenant->fresh(), [
                'idschedule' => $slot->id, 'namapelanggan' => 'Tamu', 'nomorhp' => '081234567890',
            ]);
            $payment = Payment::withoutGlobalScopes()->find($booking->idpayment);

            $this->assertSame($expected, $simulated, $type);
            $this->assertSame((float) $expected, (float) $payment->jumlah, $type);
            $this->assertSame($expected, $payment->price_breakdown['total'], $type);
        }
    }

    public function test_simulator_follows_opening_hours_and_shows_peak_and_special_date_prices(): void
    {
        OpeningHour::create(['idtenant' => $this->tenant->id, 'day_of_week' => 6, 'opens_at' => '09:00:00', 'closes_at' => '11:00:00']);
        OpeningHour::create(['idtenant' => $this->tenant->id, 'day_of_week' => 6, 'opens_at' => '13:00:00', 'closes_at' => '14:00:00']);
        $this->actingAs($this->owner)->post(route('owner.schedule.peak-rules.store'), [
            'name' => 'Siang', 'days' => [6], 'starts_at' => '13:00', 'ends_at' => '15:00', 'extra_amount' => 25000, 'service_ids' => [$this->service->id],
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->post(route('owner.schedule.special-dates.store'), [
            'tanggal' => '2026-11-07', 'price' => 80000, 'service_ids' => [$this->service->id],
        ])->assertSessionHasNoErrors();

        $hours = $this->actingAs($this->owner)
            ->getJson(route('owner.schedule.price-simulator', ['idlayanan' => $this->service->id, 'tanggal' => '2026-11-07']))
            ->assertOk()->json('hours');

        $this->assertSame(['09:00', '10:00', '13:00'], array_column($hours, 'time'));
        $this->assertSame([80000, 80000, 105000], array_column($hours, 'price'));
    }

    public function test_apply_base_price_changes_only_future_free_slots(): void
    {
        $free = $this->slot('2026-11-04', '10:00', 120000);
        $booked = $this->slot('2026-11-04', '11:00', 130000);
        $this->paidBookingOn($booked);
        $past = $this->slot('2026-11-01', '10:00', 110000);
        $earlierToday = $this->slot('2026-11-02', '07:00', 115000);
        $otherService = $this->slot('2026-11-04', '10:00', 140000, Service::factory()->create(['idtenant' => $this->tenant->id, 'is_active' => true]));
        $otherOwner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $otherTenant = Tenant::factory()->create(['iduser' => $otherOwner->id]);
        $foreign = $this->slot('2026-11-04', '10:00', 150000, Service::factory()->create(['idtenant' => $otherTenant->id, 'is_active' => true]));

        $this->actingAs($this->owner)
            ->post(route('owner.schedule.apply-base-price'), ['idlayanan' => $this->service->id])
            ->assertSessionHas('sukses', '1 slot terbuka kini memakai harga dasar.');

        $this->assertNull($free->fresh()->harga_override);
        $this->assertEquals(130000, $booked->fresh()->harga_override);
        $this->assertEquals(110000, $past->fresh()->harga_override);
        $this->assertEquals(115000, $earlierToday->fresh()->harga_override);
        $this->assertEquals(140000, $otherService->fresh()->harga_override);
        $this->assertEquals(150000, Schedule::withoutGlobalScopes()->find($foreign->id)->harga_override);
    }

    public function test_apply_base_price_refuses_another_tenants_service(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $foreignService = Service::factory()->create(['idtenant' => Tenant::factory()->create(['iduser' => $otherOwner->id])->id]);

        $this->actingAs($this->owner)
            ->post(route('owner.schedule.apply-base-price'), ['idlayanan' => $foreignService->id])
            ->assertNotFound();
    }

    public function test_bulk_preview_counts_new_and_existing_slots_without_saving(): void
    {
        $this->slot('2026-11-04', '10:00');
        $payload = [
            'jenisslot' => 'harian', 'idlayanan' => $this->service->id, 'tanggal' => '2026-11-04',
            'jammulai' => '09:00', 'jamselesai' => '12:00', 'intervalslot' => 60,
        ];

        $this->actingAs($this->owner)->postJson(route('owner.schedule.bulk-preview'), $payload)
            ->assertOk()->assertJson(['success' => true, 'to_create' => 2, 'already_exist' => 1, 'days_count' => 1]);
        $this->assertSame(1, Schedule::withoutGlobalScopes()->count());

        $this->actingAs($this->owner)->post(route('owner.schedule.bulk-store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->postJson(route('owner.schedule.bulk-preview'), $payload)
            ->assertOk()->assertJson(['to_create' => 0, 'already_exist' => 3]);
        $this->assertSame(3, Schedule::withoutGlobalScopes()->count());
    }

    public function test_bulk_creation_stores_no_weekend_price_and_reads_it_from_the_rule(): void
    {
        $this->tenant->update(['weekend_price_type' => 'multiplier', 'weekend_price_value' => 1.5]);

        $this->actingAs($this->owner)->post(route('owner.schedule.bulk-store'), [
            'jenisslot' => 'harian', 'idlayanan' => $this->service->id, 'tanggal' => '2026-11-07',
            'jammulai' => '09:00', 'jamselesai' => '10:00', 'intervalslot' => 60,
        ])->assertSessionHasNoErrors();

        $slot = Schedule::withoutGlobalScopes()->sole();
        $this->assertNull($slot->harga_override);
        $hours = $this->actingAs($this->owner)
            ->getJson(route('owner.schedule.price-simulator', ['idlayanan' => $this->service->id, 'tanggal' => '2026-11-07']))
            ->json('hours');
        $this->assertSame(150000, collect($hours)->firstWhere('time', '09:00')['price']);
    }

    public function test_runway_warns_when_no_free_slot_is_open_in_the_next_seven_days(): void
    {
        $this->service->update(['namalayanan' => 'A Kelas']);
        $this->slot('2026-11-04', '10:00');
        $this->slot('2026-11-12', '10:00');
        $later = Service::factory()->create(['idtenant' => $this->tenant->id, 'is_active' => true, 'namalayanan' => 'B Sewa']);
        $this->slot('2026-11-22', '10:00', null, $later);
        $empty = Service::factory()->create(['idtenant' => $this->tenant->id, 'is_active' => true, 'namalayanan' => 'C Kosong']);
        $soon = Service::factory()->create(['idtenant' => $this->tenant->id, 'is_active' => true, 'namalayanan' => 'D Singkat']);
        $this->slot('2026-11-05', '10:00', null, $soon);
        $this->paidBookingOn($this->slot('2026-11-20', '10:00', null, $soon));

        $services = collect($this->actingAs($this->owner)->getJson(route('owner.schedule.runway'))->assertOk()->json('services'))->keyBy('service_id');

        $this->assertSame(['open_until' => '2026-11-12', 'warning' => false], $services[$this->service->id] ? array_intersect_key($services[$this->service->id], ['open_until' => 1, 'warning' => 1]) : []);
        $this->assertTrue($services[$later->id]['warning']);
        $this->assertSame('2026-11-22', $services[$later->id]['open_until']);
        $this->assertTrue($services[$empty->id]['warning']);
        $this->assertNull($services[$empty->id]['open_until']);
        $this->assertTrue($services[$soon->id]['warning'], 'a booked slot does not extend the runway');
        $this->assertSame('2026-11-05', $services[$soon->id]['open_until']);
    }

    public function test_opening_hours_save_breaks_and_refuse_overlapping_intervals(): void
    {
        $this->actingAs($this->owner)->put(route('owner.schedule.opening-hours'), [
            'days' => [1 => [['opens_at' => '09:00', 'closes_at' => '12:00'], ['opens_at' => '13:00', 'closes_at' => '17:00']]],
            'slot_open_days_ahead' => 14,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, OpeningHour::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->where('day_of_week', 1)->count());
        $this->assertSame(14, $this->tenant->fresh()->slot_open_days_ahead);

        $this->actingAs($this->owner)->put(route('owner.schedule.opening-hours'), [
            'days' => [1 => [['opens_at' => '09:00', 'closes_at' => '12:00'], ['opens_at' => '11:00', 'closes_at' => '17:00']]],
        ])->assertSessionHasErrors('days.1');
        $this->assertSame(2, OpeningHour::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->count());
    }

    public function test_bulk_creation_refuses_dates_beyond_the_slot_opening_window(): void
    {
        $this->tenant->update(['slot_open_days_ahead' => 7]);
        $payload = ['jenisslot' => 'harian', 'idlayanan' => $this->service->id, 'jammulai' => '09:00', 'jamselesai' => '10:00', 'intervalslot' => 60];

        $this->actingAs($this->owner)->post(route('owner.schedule.bulk-store'), $payload + ['tanggal' => '2026-11-10'])->assertSessionHasErrors('tanggal');
        $this->actingAs($this->owner)->post(route('owner.schedule.bulk-store'), $payload + ['tanggal' => '2026-11-09'])->assertSessionHasNoErrors();
        $this->assertSame(1, Schedule::withoutGlobalScopes()->count());
    }

    public function test_price_rules_cannot_reach_another_tenant(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $otherTenant = Tenant::factory()->create(['iduser' => $otherOwner->id]);
        $foreignService = Service::factory()->create(['idtenant' => $otherTenant->id]);
        $foreignRule = PeakHourRule::create(['idtenant' => $otherTenant->id, 'name' => 'B', 'days' => [1], 'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'extra_amount' => 1000]);
        $foreignDate = SpecialDatePrice::create(['idtenant' => $otherTenant->id, 'service_id' => $foreignService->id, 'tanggal' => '2026-11-07', 'price' => 1000]);
        $rule = ['name' => 'X', 'days' => [1], 'starts_at' => '10:00', 'ends_at' => '11:00', 'extra_amount' => 5000];

        $this->actingAs($this->owner)->put(route('owner.schedule.peak-rules.update', $foreignRule->id), $rule)->assertNotFound();
        $this->actingAs($this->owner)->delete(route('owner.schedule.peak-rules.destroy', $foreignRule->id))->assertNotFound();
        $this->actingAs($this->owner)->delete(route('owner.schedule.special-dates.destroy', $foreignDate->id))->assertNotFound();
        $this->actingAs($this->owner)->post(route('owner.schedule.peak-rules.store'), $rule + ['service_ids' => [$foreignService->id]])->assertSessionHasErrors('service_ids');
        $this->actingAs($this->owner)->post(route('owner.schedule.special-dates.store'), ['tanggal' => '2026-11-07', 'price' => 5, 'service_ids' => [$foreignService->id]])->assertSessionHasErrors('service_ids');

        $this->assertSame('B', PeakHourRule::withoutGlobalScopes()->find($foreignRule->id)->name);
        $this->assertNotNull(SpecialDatePrice::withoutGlobalScopes()->find($foreignDate->id));
        $this->assertSame(1, PeakHourRule::withoutGlobalScopes()->count());
    }

    public function test_cashier_cannot_change_price_rules(): void
    {
        $cashier = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $cashier->id, 'role' => 'kasir', 'accepted_at' => now()]);

        $this->actingAs($cashier)->post(route('owner.schedule.peak-rules.store'), [
            'name' => 'X', 'days' => [1], 'starts_at' => '10:00', 'ends_at' => '11:00', 'extra_amount' => 5000,
        ])->assertForbidden();
        $this->actingAs($cashier)->post(route('owner.schedule.apply-base-price'), ['idlayanan' => $this->service->id])->assertForbidden();
        $this->assertSame(0, PeakHourRule::withoutGlobalScopes()->count());
    }

    public function test_storefront_placement_freezes_the_amount_against_later_price_changes(): void
    {
        $this->tenant->update(['weekend_price_type' => 'multiplier', 'weekend_price_value' => 1.5]);
        $first = $this->slot('2026-11-07', '10:00');
        $second = $this->slot('2026-11-07', '11:00', 90000);
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);

        $result = app(CreateBooking::class)->execute(
            $this->tenant->fresh(), $this->service->fresh(), '2026-11-07', ['10:00', '11:00'], [$first->id, $second->id],
            ['namapelanggan' => 'Rani', 'nomorhp' => '081234567890', 'email' => 'rani@example.com', 'catatan' => null],
        );

        $this->assertArrayNotHasKey('error', $result);
        $payment = Payment::withoutGlobalScopes()->find($result['payment']->id);
        $this->assertEquals(240000, $payment->jumlah);
        $this->assertSame(240000, $payment->price_breakdown['total']);
        $this->assertSame([150000, 90000], array_column($payment->price_breakdown['lines'], 'amount'));

        $this->tenant->update(['weekend_price_type' => 'fixed', 'weekend_price_value' => 999]);
        $this->service->update(['harga' => 1]);
        $second->update(['harga_override' => 5]);

        $labels = Booking::withoutGlobalScopes()->where('idpayment', $payment->id)->orderBy('jam')->get()->map->priceLabel->all();
        $this->assertSame(['Rp 150.000', 'Rp 90.000'], $labels);
    }

    public function test_checkout_fingerprint_changes_when_a_price_rule_changes(): void
    {
        $slot = $this->slot('2026-11-07', '10:00');
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);
        $state = app(StorefrontCheckoutState::class);
        $before = $state->fingerprintFor($this->tenant, $this->service->fresh(), '2026-11-07', [$slot->id]);

        $this->tenant->update(['weekend_price_type' => 'multiplier', 'weekend_price_value' => 2]);

        $this->assertNotSame($before, $state->fingerprintFor($this->tenant->fresh(), $this->service->fresh(), '2026-11-07', [$slot->id]));
    }
}
