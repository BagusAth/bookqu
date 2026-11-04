<?php

declare(strict_types=1);

namespace Tests\Feature\Schedule;

use App\Domain\Schedule\AvailabilityRules;
use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * BQ-108 / AC-JBH-03: a closed date with active bookings is refused until they are handled.
 */
class BlockedDateActiveBookingConflictTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Tenant $tenant;

    protected Service $service;

    protected string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'weekend_price_type' => 'none']);
        $this->service = Service::factory()->create([
            'idtenant' => $this->tenant->id,
            'is_active' => true,
            'durasi' => 60,
            'harga' => 100000,
        ]);
        $this->date = Carbon::today()->addDays(3)->toDateString();
    }

    private function closeDate(?string $date = null): TestResponse
    {
        return $this->actingAs($this->owner)->post(route('owner.schedule.availability'), [
            'tanggal_block' => $date ?? $this->date,
            'alasan' => 'Renovasi',
            'weekend_price_type' => 'none',
        ]);
    }

    public function test_closing_a_date_without_bookings_succeeds_immediately(): void
    {
        $this->bookingOnDate(Carbon::parse($this->date)->addDay()->toDateString(), ['status' => 'paid']);

        $this->closeDate()
            ->assertSessionHasNoErrors()
            ->assertRedirect('/owner/schedule');

        $this->assertTrue(OwnerBlockedDate::withoutGlobalScopes()
            ->where('idtenant', $this->tenant->id)
            ->whereDate('tanggal', $this->date)
            ->exists());
    }

    public function test_closing_a_date_with_paid_bookings_is_refused_with_the_affected_bookings(): void
    {
        $first = $this->bookingOnDate($this->date, ['status' => 'paid', 'namapelanggan' => 'Ani'], '09:00:00');
        $second = $this->bookingOnDate($this->date, ['status' => 'paid', 'namapelanggan' => 'Budi'], '11:00:00');

        $response = $this->closeDate()->assertSessionHasErrors('tanggal_block');

        $this->assertStringContainsString('2 booking aktif', session('errors')->first('tanggal_block'));
        $conflicts = session('blocked_date_conflicts');
        $this->assertSame(
            [$first->booking_code, $second->booking_code],
            array_column($conflicts['bookings'], 'booking_code')
        );
        $this->assertSame($this->date, $conflicts['tanggal']);
        $response->assertRedirect();
        $this->assertDatabaseCount('owner_blocked_dates', 0);
    }

    public function test_refused_closing_lists_the_affected_bookings_on_the_schedule_page(): void
    {
        $booking = $this->bookingOnDate($this->date, ['status' => 'paid', 'namapelanggan' => '<b>Citra</b>']);

        $this->followingRedirects()
            ->actingAs($this->owner)
            ->post(route('owner.schedule.availability'), [
                'tanggal_block' => $this->date,
                'weekend_price_type' => 'none',
            ])
            ->assertOk()
            ->assertSee('id="blocked-date-conflicts"', false)
            ->assertSee($booking->booking_code)
            ->assertSee('&lt;b&gt;Citra&lt;/b&gt;', false)
            ->assertDontSee('<b>Citra</b>', false);
    }

    public function test_closing_a_date_with_a_pending_booking_inside_its_payment_window_is_refused(): void
    {
        $payment = Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'status' => 'pending',
            'expired_at' => now()->addMinutes(10),
        ]);
        $this->bookingOnDate($this->date, ['status' => 'pending', 'idpayment' => $payment->id]);

        $this->closeDate()->assertSessionHasErrors('tanggal_block');

        $this->assertDatabaseCount('owner_blocked_dates', 0);
    }

    public function test_cancelled_and_expired_pending_bookings_do_not_block_closing_a_date(): void
    {
        $expiredPayment = Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'status' => 'pending',
            'expired_at' => now()->subHour(),
        ]);
        $this->bookingOnDate($this->date, ['status' => 'cancelled'], '09:00:00');
        $this->bookingOnDate($this->date, ['status' => 'pending', 'idpayment' => $expiredPayment->id], '10:00:00');

        $this->closeDate()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('owner_blocked_dates', 1);
    }

    public function test_bookings_of_another_tenant_do_not_block_closing_a_date(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherService = Service::factory()->create(['idtenant' => $otherTenant->id, 'durasi' => 60]);
        $otherSlot = Schedule::factory()->create([
            'idtenant' => $otherTenant->id,
            'idlayanan' => $otherService->id,
            'tanggal' => $this->date,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:59:00',
        ]);
        Booking::factory()->create([
            'idtenant' => $otherTenant->id,
            'idlayanan' => $otherService->id,
            'idschedule' => $otherSlot->id,
            'tanggalbooking' => $this->date,
            'jam' => '09:00:00',
            'status' => 'paid',
        ]);

        $this->closeDate()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('owner_blocked_dates', 1);
    }

    public function test_reopening_a_closed_date_restores_slot_bookability(): void
    {
        $slot = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->date,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:59:00',
            'status' => 'tersedia',
        ]);
        $this->closeDate()->assertSessionHasNoErrors();
        $this->assertFalse(AvailabilityRules::isSlotAvailable($slot->fresh()));

        $blocked = OwnerBlockedDate::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->firstOrFail();
        $this->actingAs($this->owner)
            ->delete(route('owner.schedule.blocked-dates.delete', $blocked->id))
            ->assertRedirect('/owner/schedule');

        $this->assertDatabaseCount('owner_blocked_dates', 0);
        $this->assertTrue(AvailabilityRules::isSlotAvailable($slot->fresh()));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function bookingOnDate(string $date, array $attributes, string $time = '09:00:00'): Booking
    {
        $slot = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $date,
            'jam_mulai' => $time,
            'jam_selesai' => Carbon::parse($time)->addMinutes(59)->format('H:i:s'),
            'status' => 'tersedia',
        ]);

        return Booking::factory()->create(array_merge([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $slot->id,
            'tanggalbooking' => $date,
            'jam' => $time,
        ], $attributes));
    }
}
