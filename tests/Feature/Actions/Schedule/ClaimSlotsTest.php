<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Schedule;

use App\Actions\Schedule\ClaimSlots;
use App\Actions\Schedule\SlotClaimRefused;
use App\Domain\Schedule\AvailabilityRules;
use App\Domain\Schedule\SlotRefusal;
use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The locked claim of the Slot bookability module (ADR-0005, BQ-012).
 */
class ClaimSlotsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-02 08:00:00');
        $this->tenant = Tenant::factory()->create();
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id]);
    }

    public function test_returns_free_slots_in_the_requested_order(): void
    {
        $late = $this->slot('11:00:00');
        $early = $this->slot('10:00:00');

        $claimed = $this->claim([$late->id, $early->id]);

        $this->assertSame([$late->id, $early->id], $claimed->pluck('id')->all());
    }

    public function test_reclaims_a_stale_pending_booking_through_the_payment_lifecycle(): void
    {
        $slot = $this->slot('10:00:00');
        [$payment, $booking] = $this->pendingBooking($slot, deadline: '2026-11-02 07:50:00');

        $claimed = $this->claim([$slot->id]);

        $this->assertSame([$slot->id], $claimed->pluck('id')->all());
        $this->assertSame('gagal', $payment->fresh()->status);
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_refuses_a_slot_held_by_a_payable_booking(): void
    {
        $slot = $this->slot('10:00:00');
        $this->pendingBooking($slot, deadline: '2026-11-02 08:10:00');

        $this->assertRefused(SlotRefusal::Occupied, [$slot->id]);
    }

    public function test_refusal_rolls_back_the_stale_cleanup(): void
    {
        $stale = $this->slot('10:00:00');
        [$payment, $booking] = $this->pendingBooking($stale, deadline: '2026-11-02 07:50:00');
        $taken = $this->slot('11:00:00');
        $this->pendingBooking($taken, deadline: '2026-11-02 08:10:00');

        $this->assertRefused(SlotRefusal::Occupied, [$stale->id, $taken->id]);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_refuses_a_schedule_of_another_tenant_before_any_cleanup(): void
    {
        $own = $this->slot('10:00:00');
        [$payment] = $this->pendingBooking($own, deadline: '2026-11-02 07:50:00');
        $foreign = Schedule::factory()->create(['tanggal' => '2026-11-03']);

        $this->assertRefused(SlotRefusal::ForeignTenant, [$own->id, $foreign->id]);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_refuses_a_schedule_of_another_service(): void
    {
        $other = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => Service::factory()->create(['idtenant' => $this->tenant->id])->id,
            'tanggal' => '2026-11-03',
        ]);

        $this->assertRefused(SlotRefusal::ForeignService, [$other->id]);
    }

    public function test_refuses_a_date_closed_by_the_owner(): void
    {
        $slot = $this->slot('10:00:00');
        OwnerBlockedDate::create(['idtenant' => $this->tenant->id, 'tanggal' => '2026-11-03', 'alasan' => 'Libur']);

        $this->assertRefused(SlotRefusal::DateClosed, [$slot->id]);
    }

    public function test_refuses_an_inactive_service(): void
    {
        $slot = $this->slot('10:00:00');
        $this->service->update(['is_active' => false]);

        $this->assertRefused(SlotRefusal::ServiceUnavailable, [$slot->id]);
    }

    public function test_refuses_a_slot_that_already_started(): void
    {
        $slot = $this->slot('07:30:00', date: '2026-11-02');

        $this->assertRefused(SlotRefusal::Past, [$slot->id]);
    }

    public function test_refuses_a_pending_booking_on_a_successful_payment_without_cancelling_it(): void
    {
        $slot = $this->slot('10:00:00');
        [$payment, $booking] = $this->pendingBooking($slot, deadline: '2026-11-02 07:50:00');
        $payment->forceFill(['status' => 'sukses'])->saveQuietly();

        $this->assertRefused(SlotRefusal::InconsistentGroup, [$slot->id]);

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame('sukses', $payment->fresh()->status);
    }

    public function test_listing_status_agrees_with_the_claim(): void
    {
        $free = $this->slot('10:00:00');
        $held = $this->slot('11:00:00');
        $this->pendingBooking($held, deadline: '2026-11-02 08:10:00');

        $this->assertSame(AvailabilityRules::STATUS_AVAILABLE, AvailabilityRules::getSlotAvailabilityStatus($free));
        $this->assertSame(AvailabilityRules::STATUS_BOOKED, AvailabilityRules::getSlotAvailabilityStatus($held));
        $this->assertSame([$free->id], $this->claim([$free->id])->pluck('id')->all());
        $this->assertRefused(SlotRefusal::Occupied, [$held->id]);
    }

    /**
     * @param  list<int>  $scheduleIds
     * @return Collection<int, Schedule>
     */
    private function claim(array $scheduleIds): Collection
    {
        return DB::transaction(fn () => app(ClaimSlots::class)->execute($this->tenant, (int) $this->service->id, $scheduleIds));
    }

    /**
     * @param  list<int>  $scheduleIds
     */
    private function assertRefused(SlotRefusal $expected, array $scheduleIds): void
    {
        try {
            $this->claim($scheduleIds);
            $this->fail("Expected the claim to be refused with {$expected->value}.");
        } catch (SlotClaimRefused $refused) {
            $this->assertSame($expected, $refused->refusal);
        }
    }

    private function slot(string $time, string $date = '2026-11-03'): Schedule
    {
        return Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $date,
            'jam_mulai' => $time,
            'jam_selesai' => substr($time, 0, 2).':59:00',
        ]);
    }

    /**
     * @return array{0: Payment, 1: Booking}
     */
    private function pendingBooking(Schedule $slot, string $deadline): array
    {
        $payment = Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'provider' => 'midtrans',
            'expired_at' => $deadline,
        ]);
        $booking = Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $slot->id,
            'idpayment' => $payment->id,
            'tanggalbooking' => $slot->tanggal,
            'jam' => $slot->jam_mulai,
        ]);

        return [$payment, $booking];
    }
}
