<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Tests\TestCase;

class EntitlementRulesTest extends TestCase
{
    public function test_can_access_feature_null_subscription(): void
    {
        $res = EntitlementRules::canAccessFeature(null, 'analytics');
        $this->assertFalse($res['allowed']);
        $this->assertEquals('no_subscription', $res['reason']);
    }

    public function test_can_access_feature_expired_subscription(): void
    {
        $sub = new Subscription([
            'status'         => SubscriptionState::STATUS_EXPIRED,
            'trial_berakhir' => null,
        ]);

        $res = EntitlementRules::canAccessFeature($sub, 'analytics');
        $this->assertFalse($res['allowed']);
        $this->assertEquals('expired', $res['reason']);
    }

    public function test_can_access_feature_active_trial(): void
    {
        $sub = new Subscription([
            'status'         => SubscriptionState::STATUS_TRIAL,
            'trial_berakhir' => Carbon::now()->addDays(5),
        ]);

        $res = EntitlementRules::canAccessFeature($sub, 'landing-page');
        $this->assertTrue($res['allowed']);
        $this->assertEquals('trial_active', $res['reason']);
    }

    public function test_can_access_feature_plan_restrictions(): void
    {
        $planSmall = new Plan(['namapaket' => 'small']);
        $planMedium = new Plan(['namapaket' => 'medium']);
        $planPro = new Plan(['namapaket' => 'pro']);

        $subSmall = new Subscription(['status' => SubscriptionState::STATUS_ACTIVE]);
        $subSmall->setRelation('plan', $planSmall);

        $subMed = new Subscription(['status' => SubscriptionState::STATUS_ACTIVE]);
        $subMed->setRelation('plan', $planMedium);

        $subPro = new Subscription(['status' => SubscriptionState::STATUS_ACTIVE]);
        $subPro->setRelation('plan', $planPro);

        // Small cannot access analytics
        $checkSmallAnalytics = EntitlementRules::canAccessFeature($subSmall, 'analytics');
        $this->assertFalse($checkSmallAnalytics['allowed']);
        $this->assertEquals('insufficient_plan', $checkSmallAnalytics['reason']);
        $this->assertStringContainsString('Medium', $checkSmallAnalytics['message']);

        // Medium can access analytics but not landing-page
        $checkMedAnalytics = EntitlementRules::canAccessFeature($subMed, 'analytics');
        $this->assertTrue($checkMedAnalytics['allowed']);

        $checkMedLanding = EntitlementRules::canAccessFeature($subMed, 'landing-page');
        $this->assertFalse($checkMedLanding['allowed']);
        $this->assertStringContainsString('Pro', $checkMedLanding['message']);

        // Pro can access both
        $this->assertTrue(EntitlementRules::canAccessFeature($subPro, 'analytics')['allowed']);
        $this->assertTrue(EntitlementRules::canAccessFeature($subPro, 'landing-page')['allowed']);
    }

    public function test_can_create_staff_quota(): void
    {
        $planSmall = new Plan(['namapaket' => 'small', 'isunlimited' => false]);
        $subSmall = new Subscription(['status' => SubscriptionState::STATUS_ACTIVE]);
        $subSmall->setRelation('plan', $planSmall);

        // Small limit is 2
        $allowed1 = EntitlementRules::canCreateStaff($subSmall, 1);
        $this->assertTrue($allowed1['allowed']);

        $blocked2 = EntitlementRules::canCreateStaff($subSmall, 2);
        $this->assertFalse($blocked2['allowed']);
        $this->assertStringContainsString('2 staf', (string) $blocked2['message']);
    }

    public function test_can_create_service_quota(): void
    {
        $planSmall = new Plan(['namapaket' => 'small', 'maxlayanan' => 5, 'isunlimited' => false]);
        $subSmall = new Subscription(['status' => SubscriptionState::STATUS_ACTIVE]);
        $subSmall->setRelation('plan', $planSmall);

        $allowed4 = EntitlementRules::canCreateService($subSmall, 4);
        $this->assertTrue($allowed4['allowed']);

        $blocked5 = EntitlementRules::canCreateService($subSmall, 5);
        $this->assertFalse($blocked5['allowed']);
        $this->assertStringContainsString('5', (string) $blocked5['message']);
    }

    public function test_can_create_booking_quota(): void
    {
        $planSmall = new Plan(['namapaket' => 'small', 'maxbooking' => 300, 'isunlimited' => false]);
        $subSmall = new Subscription(['status' => SubscriptionState::STATUS_ACTIVE]);
        $subSmall->setRelation('plan', $planSmall);

        $allowed = EntitlementRules::canCreateBooking($subSmall, 299, 1);
        $this->assertTrue($allowed['allowed']);

        $blocked = EntitlementRules::canCreateBooking($subSmall, 300, 1);
        $this->assertFalse($blocked['allowed']);
        $this->assertEquals(0, $blocked['remaining']);
        $this->assertStringContainsString('300', (string) $blocked['message']);
    }
}
