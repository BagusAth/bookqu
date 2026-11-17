<?php

namespace Tests\Feature\Schedule;

use App\Actions\Schedule\OpenAutomaticSlots;
use App\Models\OpeningHour;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** BQ-162 batch 4: automatic slot opening from opening hours (FR-SCHEDULE-015, D-026). */
class AutomaticSlotOpeningTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Tuesday; the next 30 days hold four Mondays: 23 and 30 Nov, 7 and 14 Dec.
        $this->travelTo(Carbon::parse('2026-11-17 22:00:00', 'Asia/Jakarta'));
        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'slot_open_days_ahead' => null, 'booking_max_days_ahead' => null]);
        $this->activePlan(now()->addMonths(2));
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id, 'durasi' => 60, 'is_active' => true, 'auto_open_slots' => true]);
        // Monday 09:00–12:00 and 13:00–15:00: a lunch break between the two intervals.
        OpeningHour::create(['idtenant' => $this->tenant->id, 'day_of_week' => 1, 'opens_at' => '09:00:00', 'closes_at' => '12:00:00']);
        OpeningHour::create(['idtenant' => $this->tenant->id, 'day_of_week' => 1, 'opens_at' => '13:00:00', 'closes_at' => '15:00:00']);
    }

    public function test_it_opens_slots_from_opening_hours_for_thirty_days_and_skips_the_break(): void
    {
        $created = app(OpenAutomaticSlots::class)->execute($this->tenant);

        $this->assertSame(20, $created);
        $starts = Schedule::withoutGlobalScopes()->where('idlayanan', $this->service->id)->whereDate('tanggal', '2026-11-23')->orderBy('jam_mulai')->pluck('jam_mulai')->all();
        $this->assertSame(['09:00:00', '10:00:00', '11:00:00', '13:00:00', '14:00:00'], $starts);
        $this->assertSame(0, Schedule::withoutGlobalScopes()->whereDate('tanggal', '>', '2026-12-17')->count());
    }

    public function test_running_twice_creates_nothing_new_and_keeps_closed_slots_and_dates(): void
    {
        // Stored as a plain date, as the MySQL/MariaDB date column does (SQLite would add a time).
        DB::table('owner_blocked_dates')->insert(['idtenant' => $this->tenant->id, 'tanggal' => '2026-11-23', 'alasan' => 'Libur', 'created_at' => now(), 'updated_at' => now()]);
        $closed = Schedule::factory()->create([
            'idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => '2026-11-30',
            'jam_mulai' => '09:00:00', 'jam_selesai' => '09:59:00', 'status' => 'tidak_tersedia',
        ]);

        $first = app(OpenAutomaticSlots::class)->execute($this->tenant);
        $second = app(OpenAutomaticSlots::class)->execute($this->tenant);

        $this->assertSame(14, $first);
        $this->assertSame(0, $second);
        $this->assertSame(0, Schedule::withoutGlobalScopes()->whereDate('tanggal', '2026-11-23')->count());
        $this->assertSame('tidak_tersedia', $closed->fresh()->status);
        $this->assertSame(1, Schedule::withoutGlobalScopes()->whereDate('tanggal', '2026-11-30')->where('jam_mulai', '09:00:00')->count());
    }

    public function test_it_respects_the_days_ahead_limit_switched_off_services_and_ended_plans(): void
    {
        $this->tenant->update(['slot_open_days_ahead' => 7]);
        Service::factory()->create(['idtenant' => $this->tenant->id, 'durasi' => 60, 'is_active' => true, 'auto_open_slots' => false]);

        $this->assertSame(5, app(OpenAutomaticSlots::class)->execute($this->tenant->fresh()));

        Schedule::withoutGlobalScopes()->delete();
        $this->activePlan(now()->subDay());
        $this->assertSame(0, app(OpenAutomaticSlots::class)->execute($this->tenant->fresh()));
    }

    public function test_the_nightly_command_opens_slots_only_for_tenants_that_switched_it_on(): void
    {
        $other = Tenant::factory()->create();
        $otherService = Service::factory()->create(['idtenant' => $other->id, 'durasi' => 60, 'auto_open_slots' => false]);
        OpeningHour::create(['idtenant' => $other->id, 'day_of_week' => 1, 'opens_at' => '09:00:00', 'closes_at' => '12:00:00']);

        $this->artisan('slots:open-automatic')->assertSuccessful();

        $this->assertSame(20, Schedule::withoutGlobalScopes()->where('idlayanan', $this->service->id)->count());
        $this->assertSame(0, Schedule::withoutGlobalScopes()->where('idlayanan', $otherService->id)->count());
    }

    public function test_the_service_form_saves_the_switch(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'auto_open_slots' => false]);

        $this->actingAs($this->owner)->put(route('owner.services.update', $service->id), [
            'namalayanan' => $service->namalayanan, 'harga' => 100000, 'satuan_harga' => 'sesi', 'durasi' => 60,
            'satuan_durasi' => 'menit', 'is_active' => 1, 'auto_open_slots' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertTrue($service->fresh()->auto_open_slots);
    }

    private function activePlan(Carbon $endsAt): void
    {
        Subscription::query()->delete();
        $plan = Plan::updateOrCreate(['namapaket' => 'pro'], ['hargabulanan' => 499000, 'maxlayanan' => 0, 'maxbooking' => 0, 'isunlimited' => true]);
        Subscription::create(['idtenant' => $this->tenant->id, 'idplan' => $plan->id, 'status' => 'active', 'langganan_mulai' => now()->subMonth(), 'langganan_berakhir' => $endsAt]);
    }
}
