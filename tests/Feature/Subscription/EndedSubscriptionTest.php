<?php

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\ActivateSubscription;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Actions\Subscription\SendSubscriptionReminders;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Notifications\BookingStatusChangedOwnerNotification;
use App\Notifications\SubscriptionReminderNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EndedSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ac_pkt_02_same_plan_renewal_preserves_paid_days_and_is_payment_idempotent(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $plan = Plan::create(['namapaket' => 'medium', 'hargabulanan' => 299000, 'maxlayanan' => 0, 'maxbooking' => 500]);
        Subscription::create(['idtenant' => $tenant->id, 'idplan' => $plan->id, 'status' => 'active', 'langganan_mulai' => now()->subWeeks(2), 'langganan_berakhir' => '2026-11-20 10:00:00']);
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'idplan' => $plan->id, 'tipe' => 'subscription', 'status' => 'sukses']);
        $subscription = app(ActivateSubscription::class)->execute($payment);
        $this->assertSame('2026-12-20 10:00:00', $subscription->langganan_berakhir->format('Y-m-d H:i:s'));
        $this->assertSame($subscription->id, app(ActivateSubscription::class)->execute($payment)->id);
    }

    public function test_ac_end_01_expired_owner_can_manage_existing_but_cannot_open_new_slots(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $subscription = app(CreateTrialSubscription::class)->execute($tenant);
        $subscription->update(['trial_berakhir' => now()->subDay()]);
        foreach (['/owner/dashboard', '/owner/bookings', '/owner/calendar', '/owner/customers', '/owner/reviews', '/owner/payments', '/owner/subscription'] as $path) {
            $this->actingAs($tenant->user)->get($path)->assertOk();
        }
        $this->postJson('/owner/schedule/bulk-slots', [])->assertForbidden()->assertJsonPath('code', 'subscription-ended');
        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_ac_end_02_expired_storefront_shows_neutral_copy_without_a_booking_form(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($tenant)->update(['trial_berakhir' => now()->subDay()]);
        $this->get('/'.$tenant->slug)->assertOk()->assertSee('Bisnis ini belum menerima booking online saat ini.')->assertDontSee('id="booking-program-form"', false);
    }

    public function test_an_owner_receives_only_one_trial_even_after_recreating_the_business(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $owner = $tenant->user;
        $first = app(CreateTrialSubscription::class)->execute($tenant);
        $this->assertSame($first->id, app(CreateTrialSubscription::class)->execute($tenant)->id);
        $tenant->delete();
        $replacement = Tenant::factory()->create(['iduser' => $owner->id]);
        $subscription = app(CreateTrialSubscription::class)->execute($replacement);
        $this->assertFalse(SubscriptionState::isActive($subscription));
        $this->assertNotNull($owner->fresh()->trial_used_at);
    }

    public function test_reminders_are_queued_once_at_three_days_one_day_and_the_end_day(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $subscription = app(CreateTrialSubscription::class)->execute($tenant);
        $subscription->update(['trial_berakhir' => now()->addDays(3)]);
        Notification::fake([SubscriptionReminderNotification::class]);
        $action = app(SendSubscriptionReminders::class);
        $this->assertSame(1, $action->execute());
        $this->assertSame(0, $action->execute());
        $this->travelTo(Carbon::parse('2026-11-06 10:00:00', 'Asia/Jakarta'));
        $this->assertSame(1, $action->execute());
        $this->travelTo(Carbon::parse('2026-11-07 10:00:00', 'Asia/Jakarta'));
        $this->assertSame(1, $action->execute());
        Notification::assertSentToTimes($tenant->user, SubscriptionReminderNotification::class, 3);
        $this->assertDatabaseCount('subscription_reminders', 3);
    }

    public function test_all_registered_create_routes_refuse_expired_entitlement_before_form_validation(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($tenant)->update(['trial_berakhir' => now()->subSecond()]);
        config(['modules.vouchers' => 'released', 'modules.additional-items' => 'released', 'modules.assets' => 'released']);
        $this->actingAs($tenant->user);
        foreach (['/owner/services', '/owner/programs', '/owner/categories', '/owner/schedule/bulk-slots', '/owner/bookings/walkin', '/owner/vouchers', '/owner/additional-items', '/owner/settings/assets'] as $path) {
            $this->postJson($path, [])->assertForbidden()->assertJsonPath('code', 'subscription-ended');
        }
        foreach (['owner.units.store', 'owner.promos.store', 'owner.team.invite'] as $route) {
            $this->assertTrue(EntitlementRules::requiresActiveCreation($route));
        }
    }

    public function test_plan_change_and_trial_purchase_start_on_payment_day(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $trial = app(CreateTrialSubscription::class)->execute($tenant);
        $small = Plan::create(['namapaket' => 'small', 'hargabulanan' => 149000, 'maxlayanan' => 5, 'maxbooking' => 300]);
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'idplan' => $small->id, 'tipe' => 'subscription', 'status' => 'sukses']);
        $paid = app(ActivateSubscription::class)->execute($payment);
        $this->assertSame('2026-12-04 10:00:00', $paid->langganan_berakhir->format('Y-m-d H:i:s'));
        $this->assertSame('expired', $trial->fresh()->status);
        $upgrade = Payment::factory()->create(['idtenant' => $tenant->id, 'idplan' => $trial->idplan, 'tipe' => 'subscription', 'status' => 'sukses']);
        $changed = app(ActivateSubscription::class)->execute($upgrade);
        $this->assertSame('2026-12-04 10:00:00', $changed->langganan_berakhir->format('Y-m-d H:i:s'));
        $this->assertNotSame($paid->id, $changed->id);
    }

    public function test_expired_business_can_complete_and_serve_an_existing_paid_booking(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($tenant)->update(['trial_berakhir' => now()->subDay()]);
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'status' => 'sukses', 'jumlah' => 150000]);
        $service = Service::factory()->create(['idtenant' => $tenant->id]);
        $schedule = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id, 'tanggal' => '2026-11-06']);
        $booking = Booking::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $schedule->id, 'idpayment' => $payment->id, 'status' => 'paid']);
        Notification::fake([BookingStatusChangedOwnerNotification::class]);
        $this->actingAs($tenant->user)->patch('/owner/bookings/'.$booking->id.'/status', ['status' => 'completed'])->assertRedirect()->assertSessionHas('sukses');
        $this->assertSame('completed', $booking->fresh()->status);
        Notification::assertSentTo($tenant->user, BookingStatusChangedOwnerNotification::class);
        $this->assertSame('sukses', $payment->fresh()->status);
    }
}
