<?php

namespace Tests\Feature\Owner;

use App\Actions\Schedule\ClaimSlots;
use App\Actions\Schedule\GetAvailableSchedules;
use App\Actions\Schedule\SlotClaimRefused;
use App\Actions\Tenant\GetStorefrontTenant;
use App\Domain\Schedule\SlotRefusal;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Notifications\NewBookingOwnerNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_policy_filters_storefront_slots_and_is_rechecked_under_lock(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['idtenant' => $tenant->id]);
        $slot = fn (string $start, string $end) => Schedule::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'tanggal' => '2026-11-04',
            'jam_mulai' => $start, 'jam_selesai' => $end, 'status' => 'tersedia',
        ]);
        $early = $slot('11:00', '11:59');
        $busy = $slot('13:00', '13:59');
        $near = $slot('14:00', '14:59');
        $free = $slot('15:00', '15:59');
        Booking::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $busy->id,
            'tanggalbooking' => '2026-11-04', 'jam' => '13:00', 'status' => 'paid',
        ]);

        $storefront = app(GetStorefrontTenant::class);
        $stale = $storefront->execute($tenant->id);
        $this->actingAs($tenant->user)->patch('/owner/settings/booking-policy', [
            'refunds_enabled' => true, 'cancel_before_hours' => 12, 'reschedule_before_hours' => 18,
            'booking_min_lead_hours' => 2, 'booking_max_days_ahead' => 7, 'booking_buffer_minutes' => 30,
        ])->assertSessionHas('sukses');
        $current = $storefront->execute($tenant->id);
        $this->assertSame(7, $current->booking_max_days_ahead);
        $this->assertSame(12, $current->cancel_before_hours);
        $times = collect(app(GetAvailableSchedules::class)->getTimeSlots($current, $service, '2026-11-04'))->keyBy('id');
        $this->assertFalse($times[$early->id]['is_available']);
        $this->assertFalse($times[$near->id]['is_available']);
        $this->assertTrue($times[$free->id]['is_available']);

        try {
            DB::transaction(fn () => app(ClaimSlots::class)->execute($stale, $service->id, [$near->id], '2026-11-04'));
            $this->fail('The claim did not reload the saved policy.');
        } catch (SlotClaimRefused $refusal) {
            $this->assertSame(SlotRefusal::BookingPolicy, $refusal->refusal);
        }
    }

    public function test_email_preference_is_saved_without_disabling_in_app_delivery(): void
    {
        $tenant = Tenant::factory()->create();
        $booking = Booking::factory()->create(['idtenant' => $tenant->id]);
        $owner = $tenant->user;
        $this->actingAs($owner)->patch('/owner/settings/notification-preferences', [
            'preferences' => ['new_booking' => false], 'iduser' => Tenant::factory()->create()->iduser,
        ])->assertSessionHas('sukses');
        $owner->refresh();
        $this->assertSame(['new_booking' => false], $owner->notification_preferences);
        $notification = new NewBookingOwnerNotification($booking);
        $this->assertFalse(event(new NotificationSending($owner, $notification, 'mail'), halt: true));
        $this->assertTrue(event(new NotificationSending($owner, $notification, 'database'), halt: true));
    }
}
