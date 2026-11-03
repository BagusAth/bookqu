<?php

namespace Tests\Feature\Subscription;

use App\Actions\Booking\ValidateBookingDate;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\Subscription\EntitlementRules;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExpiredEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_and_missing_subscriptions_cannot_create_or_keep_unlimited_entitlements(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $plan = new Plan(['namapaket' => 'pro', 'isunlimited' => true]);
        $trial = new Subscription(['status' => 'trial', 'trial_berakhir' => now()->subDay()]);
        $trial->setRelation('plan', $plan);
        $paid = new Subscription(['status' => 'active', 'langganan_berakhir' => now()->subDay()]);
        $paid->setRelation('plan', $plan);
        $missingPlan = new Subscription(['status' => 'active']);
        $missingPlan->setRelation('plan', null);
        foreach ([$trial, $paid, null, $missingPlan] as $subscription) {
            $this->assertFalse(EntitlementRules::canAccessFeature($subscription, 'analytics')['allowed']);
            $this->assertFalse(EntitlementRules::canCreateBooking($subscription, 0)['allowed']);
            $this->assertFalse(EntitlementRules::canCreateService($subscription, 0)['allowed']);
            $this->assertFalse(EntitlementRules::canCreateStaff($subscription, 0)['allowed']);
            $this->assertFalse(EntitlementRules::isUnlimitedBooking($subscription));
            $this->assertFalse(EntitlementRules::isUnlimitedServices($subscription));
            $this->assertFalse(EntitlementRules::isUnlimitedStaff($subscription));
        }
    }

    public function test_badge_and_features_reject_an_expired_pro_plan_without_the_scheduler(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $subscription = new Subscription(['status' => 'trial', 'trial_berakhir' => now()->subDay()]);
        $subscription->setRelation('plan', new Plan(['namapaket' => 'pro']));
        $this->assertFalse(EntitlementRules::isPro($subscription));
        $this->assertFalse(EntitlementRules::canAccessFeature($subscription, 'analytics')['allowed']);
        $subscription->trial_berakhir = now()->addDay();
        $this->assertTrue(EntitlementRules::isPro($subscription));
        $this->assertTrue(EntitlementRules::canAccessFeature($subscription, 'analytics')['allowed']);
    }

    public function test_expired_owner_sees_renewal_banner_and_can_buy_a_plan(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::factory()->create(['iduser' => $owner->id]);
        $subscription = app(CreateTrialSubscription::class)->execute($tenant);
        $subscription->update(['trial_berakhir' => now()->subDay()]);
        $this->actingAs($owner)->get('/owner/dashboard')->assertOk()->assertSee('Langganan telah berakhir');
        $this->get('/owner/subscription')->assertOk();
    }

    public function test_expired_storefront_refuses_a_date_with_a_neutral_message(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['idtenant' => $tenant->id]);
        $subscription = app(CreateTrialSubscription::class)->execute($tenant);
        $subscription->update(['trial_berakhir' => now()->subDay()]);
        app(TenantContext::class)->setTenantId($tenant->id);
        try {
            app(ValidateBookingDate::class)->execute($tenant, $service, '2026-11-05');
            $this->fail('Expired business accepted a booking date.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Bisnis ini belum menerima booking online saat ini.'], $exception->errors()['tanggal']);
        }
    }
}
