<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Subscription\PlanCapability;
use App\Models\Plan;
use PHPUnit\Framework\TestCase;

class PlanCapabilityTest extends TestCase
{
    public function test_plan_levels(): void
    {
        $this->assertEquals(1, PlanCapability::getPlanLevel('small'));
        $this->assertEquals(2, PlanCapability::getPlanLevel('medium'));
        $this->assertEquals(3, PlanCapability::getPlanLevel('pro'));
        $this->assertEquals(1, PlanCapability::getPlanLevel('unknown'));
    }

    public function test_staff_limits(): void
    {
        $this->assertEquals(2, PlanCapability::getStaffLimit('small'));
        $this->assertEquals(15, PlanCapability::getStaffLimit('medium'));
        $this->assertEquals(0, PlanCapability::getStaffLimit('pro'));
        $this->assertEquals(0, PlanCapability::getStaffLimit('small', isTrial: true));
        $this->assertEquals(0, PlanCapability::getStaffLimit('small', isTrial: false, isUnlimited: true));
    }

    public function test_service_limits(): void
    {
        $planSmall = new Plan(['namapaket' => 'small', 'maxlayanan' => 5, 'isunlimited' => false]);
        $planPro = new Plan(['namapaket' => 'pro', 'maxlayanan' => 0, 'isunlimited' => true]);

        $this->assertEquals(5, PlanCapability::getServiceLimit($planSmall));
        $this->assertEquals(0, PlanCapability::getServiceLimit($planPro));
        $this->assertEquals(0, PlanCapability::getServiceLimit($planSmall, isTrial: true));
        $this->assertEquals(0, PlanCapability::getServiceLimit(null));
    }

    public function test_booking_limits(): void
    {
        $planSmall = new Plan(['namapaket' => 'small', 'maxbooking' => 300, 'isunlimited' => false]);
        $planPro = new Plan(['namapaket' => 'pro', 'maxbooking' => 0, 'isunlimited' => true]);

        $this->assertEquals(300, PlanCapability::getBookingLimit($planSmall));
        $this->assertEquals(0, PlanCapability::getBookingLimit($planPro));
        $this->assertEquals(0, PlanCapability::getBookingLimit($planSmall, isTrial: true));
    }

    public function test_feature_permissions(): void
    {
        $this->assertFalse(PlanCapability::isFeatureAllowed('small', 'analytics'));
        $this->assertTrue(PlanCapability::isFeatureAllowed('medium', 'analytics'));
        $this->assertTrue(PlanCapability::isFeatureAllowed('pro', 'analytics'));

        $this->assertFalse(PlanCapability::isFeatureAllowed('small', 'landing-page'));
        $this->assertFalse(PlanCapability::isFeatureAllowed('medium', 'landing-page'));
        $this->assertTrue(PlanCapability::isFeatureAllowed('pro', 'landing-page'));

        // Trial bypasses all feature restrictions
        $this->assertTrue(PlanCapability::isFeatureAllowed('small', 'analytics', isTrial: true));
        $this->assertTrue(PlanCapability::isFeatureAllowed('small', 'landing-page', isTrial: true));
    }
}
