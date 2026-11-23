<?php

declare(strict_types=1);

namespace Tests\Feature\Schedule;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BQ-107: bulk slot creation is capped and refuses past dates; deleting a slot follows
 * the single occupancy rule (INV-BOOK-03).
 */
class BulkScheduleLimitsAndDeleteOccupancyTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Tenant $tenant;

    protected Service $service;

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
        app(CreateTrialSubscription::class)->execute($this->tenant);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function rangePayload(array $overrides = []): array
    {
        return array_merge([
            'jenisslot' => 'rentang',
            'idlayanan' => $this->service->id,
            'tanggalmulai' => Carbon::tomorrow()->toDateString(),
            'tanggalselesai' => Carbon::tomorrow()->addDays(6)->toDateString(),
            'jammulai' => '09:00',
            'jamselesai' => '10:00',
            'intervalslot' => 60,
        ], $overrides);
    }

    public function test_bulk_range_longer_than_ninety_days_is_rejected(): void
    {
        $start = Carbon::tomorrow();

        $this->actingAs($this->owner)
            ->post(route('owner.schedule.bulk-store'), $this->rangePayload([
                'tanggalmulai' => $start->toDateString(),
                'tanggalselesai' => $start->copy()->addDays(91)->toDateString(),
            ]))
            ->assertSessionHasErrors('tanggalselesai');

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_bulk_range_of_ninety_days_creates_one_slot_per_day(): void
    {
        $start = Carbon::tomorrow();

        $this->actingAs($this->owner)
            ->post(route('owner.schedule.bulk-store'), $this->rangePayload([
                'tanggalmulai' => $start->toDateString(),
                'tanggalselesai' => $start->copy()->addDays(90)->toDateString(),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/owner/schedule');

        $this->assertDatabaseCount('schedules', 91);
        $this->assertDatabaseHas('schedules', [
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $start->copy()->addDays(90)->toDateString().' 00:00:00',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:59:00',
            'status' => 'tersedia',
        ]);
        $this->assertNotNull(Schedule::withoutGlobalScopes()->first()?->created_at);
    }

    public function test_bulk_creation_skips_slots_that_already_exist(): void
    {
        $date = Carbon::tomorrow()->toDateString();
        Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $date,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '10:59:00',
            'status' => 'tersedia',
        ]);

        $this->actingAs($this->owner)
            ->post(route('owner.schedule.bulk-store'), [
                'jenisslot' => 'harian',
                'idlayanan' => $this->service->id,
                'tanggal' => $date,
                'jammulai' => '09:00',
                'jamselesai' => '12:00',
                'intervalslot' => 60,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('schedules', 3);
        $this->assertSame(1, Schedule::withoutGlobalScopes()->where('jam_mulai', '10:00:00')->count());
    }

    public function test_bulk_daily_slots_in_the_past_are_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.schedule.bulk-store'), [
                'jenisslot' => 'harian',
                'idlayanan' => $this->service->id,
                'tanggal' => Carbon::yesterday()->toDateString(),
                'jammulai' => '09:00',
                'jamselesai' => '12:00',
                'intervalslot' => 60,
            ])
            ->assertSessionHasErrors('tanggal');

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_bulk_range_starting_in_the_past_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.schedule.bulk-store'), $this->rangePayload([
                'tanggalmulai' => Carbon::today()->subDays(3)->toDateString(),
                'tanggalselesai' => Carbon::tomorrow()->toDateString(),
            ]))
            ->assertSessionHasErrors('tanggalmulai');

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_owner_can_delete_a_slot_whose_only_booking_is_an_expired_pending_booking(): void
    {
        $slot = $this->slot();
        $payment = Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'status' => 'pending',
            'expired_at' => now()->subHour(),
        ]);
        $this->bookingOn($slot, ['status' => 'pending', 'idpayment' => $payment->id]);

        $this->actingAs($this->owner)
            ->delete(route('owner.schedule.slots.destroy', $slot->id))
            ->assertRedirect('/owner/schedule');

        $this->assertDatabaseMissing('schedules', ['id' => $slot->id]);
        $this->assertNotSame('pending', $payment->fresh()->status);
    }

    public function test_owner_cannot_delete_a_slot_with_a_paid_booking(): void
    {
        $slot = $this->slot();
        $this->bookingOn($slot, ['status' => 'paid']);

        $this->actingAs($this->owner)
            ->delete(route('owner.schedule.slots.destroy', $slot->id))
            ->assertForbidden();

        $this->assertDatabaseHas('schedules', ['id' => $slot->id]);
    }

    public function test_owner_cannot_delete_a_slot_with_a_pending_booking_inside_its_payment_window(): void
    {
        $slot = $this->slot();
        $payment = Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'status' => 'pending',
            'expired_at' => now()->addMinutes(10),
        ]);
        $booking = $this->bookingOn($slot, ['status' => 'pending', 'idpayment' => $payment->id]);

        $this->actingAs($this->owner)
            ->delete(route('owner.schedule.slots.destroy', $slot->id))
            ->assertForbidden();

        $this->assertDatabaseHas('schedules', ['id' => $slot->id]);
        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    private function slot(): Schedule
    {
        return Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => Carbon::tomorrow()->toDateString(),
            'jam_mulai' => '14:00:00',
            'jam_selesai' => '14:59:00',
            'status' => 'tersedia',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function bookingOn(Schedule $slot, array $attributes): Booking
    {
        return Booking::factory()->create(array_merge([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $slot->id,
            'tanggalbooking' => Carbon::tomorrow()->toDateString(),
            'jam' => '14:00:00',
        ], $attributes));
    }
}
