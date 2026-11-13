<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\CreateWalkInBooking;
use App\Actions\Booking\RescheduleBooking;
use App\Actions\Schedule\GetAvailableSchedules;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\Booking\BookingSeats;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\Unit;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * BQ-151 (FR-BOOKING-021, AC-KAL-05), BQ-152 (FR-UNIT-001..004, AC-UNT-01..03) and BQ-153
 * (FR-BOOKING-020, ADR-0018). The clock is Monday 2026-11-02 08:00 WIB.
 */
class CapacityUnitsAndNoShowTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2026-11-03';

    protected User $owner;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00', 'Asia/Jakarta'));
        Mail::fake();
        Notification::fake();

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        app(CreateTrialSubscription::class)->execute($this->tenant);
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);
    }

    private function service(int $capacity = 1, ?Tenant $tenant = null): Service
    {
        return Service::factory()->create(['idtenant' => ($tenant ?? $this->tenant)->id, 'is_active' => true, 'harga' => 0, 'durasi' => 60, 'kapasitas' => $capacity]);
    }

    private function slot(Service $service, string $start = '10:00', string $date = self::DATE, int $minutes = 60): Schedule
    {
        return Schedule::factory()->create([
            'idtenant' => $service->idtenant,
            'idlayanan' => $service->id,
            'tanggal' => $date,
            'jam_mulai' => $start.':00',
            'jam_selesai' => Carbon::parse($start)->addMinutes($minutes - 1)->format('H:i:s'),
            'status' => 'tersedia',
        ]);
    }

    /** @return array<string, mixed> */
    private function book(Service $service, Schedule $slot, int $participants = 1): array
    {
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);

        return app(CreateBooking::class)->execute(
            $this->tenant->fresh(),
            $service,
            self::DATE,
            [substr((string) $slot->jam_mulai, 0, 5)],
            [$slot->id],
            ['namapelanggan' => 'Rani', 'nomorhp' => '081234567890', 'email' => 'rani@example.com', 'catatan' => null],
            $participants,
        );
    }

    private function paidBooking(Schedule $slot, string $status = 'paid', int $participants = 1, ?Payment $payment = null): Booking
    {
        $payment ??= Payment::factory()->create(['idtenant' => $slot->idtenant, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 100000]);

        return Booking::factory()->create([
            'idtenant' => $slot->idtenant,
            'idlayanan' => $slot->idlayanan,
            'idschedule' => $slot->id,
            'idpayment' => $payment->id,
            'tanggalbooking' => $slot->tanggal,
            'jam' => $slot->jam_mulai,
            'status' => $status,
            'peserta' => $participants,
        ]);
    }

    private function shareUnit(Service ...$services): Unit
    {
        $unit = Unit::factory()->create(['idtenant' => $this->tenant->id]);
        $unit->services()->sync(array_map(fn (Service $service) => $service->id, $services));

        return $unit;
    }

    /** The trial includes every Pro capability; the module is released for the test. */
    private function asPro(): void
    {
        config(['modules.units' => 'released']);
    }

    // ── BQ-151: capacity and seats ────────────────────────────────────────────

    public function test_seat_index_status_list_equals_the_occupancy_statuses(): void
    {
        $definition = DB::getDriverName() === 'sqlite'
            ? (string) DB::table('sqlite_master')->where('name', 'booking_seats')->value('sql')
            : (string) DB::table('information_schema.columns')->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'booking_seats')->where('column_name', 'active_idschedule')
                ->selectRaw('generation_expression as expression')->value('expression');

        // MySQL stores the literals as _utf8mb4\'paid\'
        preg_match_all("/\\\\?'([a-z_]+)\\\\?'/", substr($definition, (int) stripos($definition, 'case')), $matches);
        $this->assertEqualsCanonicalizing(BookingSeats::INDEXED_STATUSES, array_values(array_unique($matches[1])));
    }

    public function test_slot_accepts_participants_up_to_capacity_and_refuses_more(): void
    {
        $service = $this->service(capacity: 3);
        $slot = $this->slot($service);

        $this->assertArrayNotHasKey('error', $this->book($service, $slot, 2));
        $tooMany = $this->book($service, $slot, 2);
        $this->assertStringContainsString('Sisa tempat', $tooMany['error']);
        $this->assertArrayNotHasKey('error', $this->book($service, $slot, 1));
        $this->assertStringContainsString('sudah dibooking', $this->book($service, $slot, 1)['error']);

        $this->assertSame(3, (int) Booking::where('idschedule', $slot->id)->sum('peserta'));
        $this->assertSame([0, 1, 2], DB::table('booking_seats')->where('active_idschedule', $slot->id)->orderBy('seat_no')->pluck('seat_no')->map(fn ($s) => (int) $s)->all());
    }

    public function test_paid_checkout_stores_participants_and_charges_per_participant(): void
    {
        Http::fake(['*' => Http::response([], 500)]);
        $service = $this->service(capacity: 4);
        $service->update(['harga' => 50000]);
        $slot = $this->slot($service);

        $this->book($service->fresh(), $slot, 3);

        $booking = Booking::where('idschedule', $slot->id)->firstOrFail();
        $this->assertSame(3, $booking->peserta);
        $this->assertSame('150000.00', (string) $booking->payment?->jumlah);
    }

    public function test_listing_shows_remaining_capacity_from_active_participants(): void
    {
        $service = $this->service(capacity: 4);
        $slot = $this->slot($service);
        $this->paidBooking($slot, participants: 3);
        $this->paidBooking($slot, 'cancelled', participants: 1);

        $slots = app(GetAvailableSchedules::class)->getTimeSlots($this->tenant, $service, self::DATE);

        // AC-KAL-05: counts follow the participants of active bookings, not cancelled ones
        $this->assertSame(1, $slots[0]['seats_left']);
        $this->assertTrue($slots[0]['is_available']);
    }

    public function test_seat_index_rejects_a_booking_beyond_capacity_written_without_the_claim(): void
    {
        $service = $this->service(capacity: 2);
        $slot = $this->slot($service);
        $this->paidBooking($slot, participants: 2);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->paidBooking($slot);
    }

    public function test_cancelled_booking_frees_its_seats(): void
    {
        $service = $this->service(capacity: 1);
        $slot = $this->slot($service);
        $first = $this->paidBooking($slot);
        $first->update(['status' => 'cancelled']);

        $this->assertModelExists($this->paidBooking($slot));
    }

    public function test_walk_in_uses_participants_and_capacity(): void
    {
        $service = $this->service(capacity: 2);
        $slot = $this->slot($service);

        $booking = app(CreateWalkInBooking::class)->execute($this->tenant->fresh(), ['idschedule' => $slot->id, 'namapelanggan' => 'Budi', 'nomorhp' => '081234567890', 'peserta' => 2]);
        $this->assertSame(2, $booking?->peserta);

        $this->assertNull(app(CreateWalkInBooking::class)->execute($this->tenant->fresh(), ['idschedule' => $slot->id, 'namapelanggan' => 'Sari', 'nomorhp' => '081234567891']));
    }

    // ── BQ-152: units ──────────────────────────────────────────────────────────

    public function test_unit_refuses_an_overlapping_booking_of_another_service_at_checkout_walk_in_and_reschedule(): void
    {
        $tournament = $this->service();
        $rental = $this->service();
        $this->shareUnit($tournament, $rental);
        $this->paidBooking($this->slot($tournament, '10:00'));
        $rentalSlot = $this->slot($rental, '10:30');

        // AC-UNT-01: checkout, walk-in and reschedule
        $this->assertStringContainsString('Unit untuk jam 10:30', $this->book($rental, $rentalSlot)['error']);
        $this->assertNull(app(CreateWalkInBooking::class)->execute($this->tenant->fresh(), ['idschedule' => $rentalSlot->id, 'namapelanggan' => 'Budi', 'nomorhp' => '081234567890']));

        $moved = $this->paidBooking($this->slot($rental, '13:00'));
        $result = app(RescheduleBooking::class)->execute($moved, $rentalSlot->id, 'owner');
        $this->assertFalse($result['success']);
        $this->assertSame('SLOT_TAKEN', $result['error_code']);

        // A slot that does not overlap stays bookable, and is listed as such
        $free = $this->slot($rental, '11:00');
        $listed = collect(app(GetAvailableSchedules::class)->getTimeSlots($this->tenant, $rental, self::DATE))->keyBy('id');
        $this->assertFalse($listed[$rentalSlot->id]['is_available']);
        $this->assertTrue($listed[$free->id]['is_available']);
        $this->assertArrayNotHasKey('error', $this->book($rental, $free));
    }

    public function test_inactive_unit_does_not_block(): void
    {
        $tournament = $this->service();
        $rental = $this->service();
        $this->shareUnit($tournament, $rental)->update(['is_active' => false]);
        $this->paidBooking($this->slot($tournament, '10:00'));

        $this->assertArrayNotHasKey('error', $this->book($rental, $this->slot($rental, '10:00')));
    }

    public function test_unit_page_lists_existing_conflicts_and_refuses_a_change_that_creates_new_ones(): void
    {
        $this->asPro();
        $tournament = $this->service();
        $rental = $this->service();
        $a = $this->paidBooking($this->slot($tournament, '10:00'));
        $b = $this->paidBooking($this->slot($rental, '10:00'));

        // Linking both services would create the overlap: refused, listing the bookings
        $this->actingAs($this->owner)->post(route('owner.units.store'), [
            'name' => 'Lapangan A', 'type' => 'Lapangan', 'kapasitas' => 1, 'service_ids' => [$tournament->id, $rental->id],
        ])->assertSessionHasErrors('service_ids');
        $this->assertSame(0, Unit::count());

        // Data from before units existed is reported, not repaired (AC-UNT-02)
        $this->shareUnit($tournament, $rental);
        $this->actingAs($this->owner)->getJson(route('owner.units'))
            ->assertOk()
            ->assertJsonPath('conflicts.0.booking_ids', [min($a->id, $b->id), max($a->id, $b->id)]);
        $this->assertSame('paid', $a->fresh()->status);
    }

    public function test_unit_with_future_bookings_cannot_be_deleted_and_other_tenants_units_are_hidden(): void
    {
        $this->asPro();
        $service = $this->service();
        $unit = $this->shareUnit($service);
        $this->paidBooking($this->slot($service));

        $this->actingAs($this->owner)->delete(route('owner.units.destroy', $unit))->assertSessionHasErrors('unit');
        $this->assertModelExists($unit);

        $other = Unit::factory()->create();
        $this->actingAs($this->owner)->put(route('owner.units.update', $other), ['name' => 'X', 'type' => 'Y', 'kapasitas' => 1])->assertNotFound();
    }

    public function test_unit_routes_are_refused_below_pro(): void
    {
        config(['modules.units' => 'released']);
        $small = Plan::create(['namapaket' => 'small', 'hargabulanan' => 169000, 'maxlayanan' => 5, 'maxbooking' => 300]);
        Subscription::where('idtenant', $this->tenant->id)->update(['idplan' => $small->id, 'status' => 'active', 'langganan_berakhir' => now()->addMonth()]);

        // AC-UNT-03: the Small plan does not include units
        $this->actingAs($this->owner)->get(route('owner.units'))->assertRedirect(route('owner.subscription'));
        $this->actingAs($this->owner)->post(route('owner.units.store'), ['name' => 'A', 'type' => 'B', 'kapasitas' => 1])->assertRedirect(route('owner.subscription'));
        $this->assertSame(0, Unit::count());
    }

    // ── BQ-153: no-show ────────────────────────────────────────────────────────

    public function test_no_show_is_marked_only_after_the_session_for_the_whole_group_without_touching_payment(): void
    {
        $service = $this->service();
        $payment = Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 200000]);
        $first = $this->paidBooking($this->slot($service, '10:00'), payment: $payment);
        $second = $this->paidBooking($this->slot($service, '11:00'), payment: $payment);

        $this->actingAs($this->owner)->patch(route('owner.bookings.status', $first), ['status' => 'no_show'])->assertSessionHasErrors('error');
        $this->assertSame('paid', $first->fresh()->status);

        $this->travelTo(Carbon::parse(self::DATE.' 12:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->owner)->patch(route('owner.bookings.status', $first), ['status' => 'no_show'])->assertSessionHasNoErrors();

        $this->assertSame(['no_show', 'no_show'], [$first->fresh()->status, $second->fresh()->status]);
        $this->assertSame('sukses', $payment->fresh()->status);
        $this->assertSame('200000.00', (string) $payment->fresh()->jumlah);
        // no_show keeps occupying its seats (ADR-0018 decision 4)
        $this->assertSame(2, DB::table('booking_seats')->whereNotNull('active_idschedule')->count());
    }

    public function test_no_show_comes_only_from_paid_and_is_terminal(): void
    {
        $this->travelTo(Carbon::parse(self::DATE.' 12:00:00', 'Asia/Jakarta'));
        $service = $this->service();
        $completed = $this->paidBooking($this->slot($service, '09:00'), 'completed');
        $noShow = $this->paidBooking($this->slot($service, '10:00'), 'no_show');

        $this->actingAs($this->owner)->patch(route('owner.bookings.status', $completed), ['status' => 'no_show'])->assertSessionHasErrors('error');
        $this->actingAs($this->owner)->patch(route('owner.bookings.status', $noShow), ['status' => 'completed'])->assertSessionHasErrors('error');
        $this->assertSame(['completed', 'no_show'], [$completed->fresh()->status, $noShow->fresh()->status]);
    }

    public function test_cashier_may_mark_a_no_show(): void
    {
        $this->travelTo(Carbon::parse(self::DATE.' 12:00:00', 'Asia/Jakarta'));
        $booking = $this->paidBooking($this->slot($this->service()));
        $kasir = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $kasir->id, 'role' => 'kasir', 'accepted_at' => now()]);

        $this->actingAs($kasir)->patch(route('owner.bookings.status', $booking), ['status' => 'no_show'])->assertSessionHasNoErrors();
        $this->assertSame('no_show', $booking->fresh()->status);
    }
}
