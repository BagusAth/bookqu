<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Booking;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KalenderPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_walk_in_confirmation_links_to_the_new_booking_panel(): void
    {
        $tenant = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($tenant);
        $cashier = User::factory()->create(['role' => 'owner']);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $cashier->id, 'role' => 'kasir', 'accepted_at' => now()]);
        $service = Service::factory()->create(['idtenant' => $tenant->id]);
        $slot = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id]);
        $calendar = route('owner.calendar', ['date' => $slot->tanggal->toDateString()]);

        $response = $this->actingAs($cashier)->from($calendar)->post(route('owner.bookings.walkin'), [
            'idschedule' => $slot->id, 'namapelanggan' => 'Rani Walk-In', 'nomorhp' => '081234567890',
        ])->assertRedirect($calendar)->assertSessionHasNoErrors()->assertSessionHas('walkin_booking');
        $booking = Booking::withoutGlobalScopes()->where('idtenant', $tenant->id)->where('idschedule', $slot->id)->sole();
        $cookie = config('session.cookie');
        $this->withCookie($cookie, $response->getCookie($cookie)->getValue())->get($calendar)->assertOk()
            ->assertSee('href="'.route('owner.bookings', ['panel' => $booking->id]).'"', false);
    }

    public function test_pro_calendar_shows_a_slot_under_each_active_unit_while_small_uses_services(): void
    {
        config(['modules.units' => 'released']);
        $tenant = Tenant::factory()->create();
        $subscription = app(CreateTrialSubscription::class)->execute($tenant);
        $active = Unit::factory()->create(['idtenant' => $tenant->id, 'name' => 'Ruang A', 'is_active' => true]);
        $inactive = Unit::factory()->create(['idtenant' => $tenant->id, 'name' => 'Ruang B', 'is_active' => false]);
        $service = Service::factory()->create(['idtenant' => $tenant->id, 'namalayanan' => 'Sesi Studio']);
        $service->units()->attach([$active->id, $inactive->id]);
        $slot = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id]);
        $url = route('owner.calendar', ['date' => $slot->tanggal->toDateString(), 'kolom' => 'unit']);

        $day = $this->actingAs($tenant->user)->get($url)->assertOk()->assertViewHas('unitMode', true)->viewData('dayGrid');

        $this->assertSame(['Ruang A'], array_column($day['columns'], 'name'));
        $this->assertSame($slot->id, $day['columns'][0]['slots'][0]['id']);

        $small = Plan::create(['namapaket' => 'small', 'hargabulanan' => 149000, 'maxlayanan' => 5, 'maxbooking' => 300]);
        $subscription->update(['idplan' => $small->id, 'status' => 'active', 'langganan_berakhir' => now()->addMonth()]);

        $day = $this->get($url)->assertOk()->assertViewHas('unitMode', false)->viewData('dayGrid');

        $this->assertSame(['Sesi Studio'], array_column($day['columns'], 'name'));
    }

    public function test_views_show_the_same_bookings_and_only_count_occupying_seats(): void
    {
        $this->travelTo(CarbonImmutable::parse('2028-01-31 08:00:00', 'Asia/Jakarta'));
        $owner = User::factory()->create(['role' => 'owner']);
        $tenant = Tenant::factory()->create(['iduser' => $owner->id]);
        app(CreateTrialSubscription::class)->execute($tenant);
        $service = Service::factory()->create(['idtenant' => $tenant->id, 'kapasitas' => 5]);
        $slot = Schedule::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'tanggal' => '2028-01-31',
            'jam_mulai' => '10:00', 'jam_selesai' => '10:59', 'status' => 'tersedia',
        ]);
        $booking = Booking::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $slot->id,
            'tanggalbooking' => $slot->tanggal, 'jam' => $slot->jam_mulai, 'status' => 'paid', 'peserta' => 3,
        ]);
        Booking::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $slot->id,
            'tanggalbooking' => $slot->tanggal, 'jam' => $slot->jam_mulai, 'status' => 'cancelled', 'peserta' => 2,
        ]);

        $this->actingAs($owner);
        foreach (['day', 'week', 'month'] as $view) {
            $page = $this->get(route('owner.calendar', ['view' => $view, 'date' => '2028-01-31']))->assertOk();
            if ($view === 'day') {
                $page->assertSee($booking->namapelanggan)->assertSee('3/5 peserta');
            } elseif ($view === 'week') {
                $page->assertSee($booking->namapelanggan);
            } else {
                $page->assertSee(route('owner.calendar', ['view' => 'day', 'date' => '2028-01-31']));
                $this->assertSame(1, $page->viewData('days')['2028-01-31']['occupied']);
            }
            $card = $page->viewData('days')['2028-01-31']['slots'][0];
            $this->assertContains(route('owner.bookings.panel', $booking->id), array_column($card['bookings'], 'url'));
            $this->assertFalse($card['edit']);
        }
        $month = $this->get(route('owner.calendar', ['view' => 'month', 'date' => '2028-01-31']))->assertOk();
        $this->assertSame('2028-02-01', $month->viewData('next')->toDateString());
        $leapMonth = $this->get(route('owner.calendar', ['view' => 'month', 'date' => '2028-02-29']))->assertOk();
        $this->assertSame('2028-01-01', $leapMonth->viewData('previous')->toDateString());
        $this->assertSame('2028-03-01', $leapMonth->viewData('next')->toDateString());
    }
}
