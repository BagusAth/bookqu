<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Subscription\SubscriptionState;
use App\Models\Subscription;
use Carbon\Carbon;
use Tests\TestCase;

class SubscriptionStateTest extends TestCase
{
    public function test_null_subscription_is_expired_and_not_active(): void
    {
        $this->assertTrue(SubscriptionState::isExpired(null));
        $this->assertFalse(SubscriptionState::isActive(null));
        $this->assertEquals(0, SubscriptionState::getRemainingTrialDays(null));
    }

    public function test_active_trial_is_active_and_not_expired(): void
    {
        $sub = new Subscription([
            'status'         => SubscriptionState::STATUS_TRIAL,
            'trial_berakhir' => Carbon::now()->addDays(5),
        ]);

        $this->assertFalse(SubscriptionState::isExpired($sub));
        $this->assertTrue(SubscriptionState::isActive($sub));
        $this->assertEquals(5, SubscriptionState::getRemainingTrialDays($sub));
    }

    public function test_expired_trial_is_expired_and_not_active(): void
    {
        $sub = new Subscription([
            'status'         => SubscriptionState::STATUS_TRIAL,
            'trial_berakhir' => Carbon::now()->subDay(),
        ]);

        $this->assertTrue(SubscriptionState::isExpired($sub));
        $this->assertFalse(SubscriptionState::isActive($sub));
        $this->assertEquals(0, SubscriptionState::getRemainingTrialDays($sub));
    }

    public function test_explicit_expired_or_cancelled_status_is_expired(): void
    {
        $subExpired = new Subscription(['status' => SubscriptionState::STATUS_EXPIRED]);
        $subCancelled = new Subscription(['status' => SubscriptionState::STATUS_CANCELLED]);

        $this->assertTrue(SubscriptionState::isExpired($subExpired));
        $this->assertFalse(SubscriptionState::isActive($subExpired));

        $this->assertTrue(SubscriptionState::isExpired($subCancelled));
        $this->assertFalse(SubscriptionState::isActive($subCancelled));
    }

    public function test_active_paid_subscription_with_future_date_is_active(): void
    {
        $sub = new Subscription([
            'status'             => SubscriptionState::STATUS_ACTIVE,
            'langganan_berakhir' => Carbon::now()->addMonth(),
        ]);

        $this->assertFalse(SubscriptionState::isExpired($sub));
        $this->assertTrue(SubscriptionState::isActive($sub));
    }

    public function test_active_paid_subscription_past_end_date_is_expired(): void
    {
        $sub = new Subscription([
            'status'             => SubscriptionState::STATUS_ACTIVE,
            'langganan_berakhir' => Carbon::now()->subMinute(),
        ]);

        $this->assertTrue(SubscriptionState::isExpired($sub));
        $this->assertFalse(SubscriptionState::isActive($sub));
    }

    public function test_transitions(): void
    {
        $this->assertTrue(SubscriptionState::canTransition(SubscriptionState::STATUS_TRIAL, SubscriptionState::STATUS_ACTIVE));
        $this->assertTrue(SubscriptionState::canTransition(SubscriptionState::STATUS_TRIAL, SubscriptionState::STATUS_EXPIRED));
        $this->assertTrue(SubscriptionState::canTransition(SubscriptionState::STATUS_ACTIVE, SubscriptionState::STATUS_EXPIRED));
        $this->assertTrue(SubscriptionState::canTransition(SubscriptionState::STATUS_EXPIRED, SubscriptionState::STATUS_ACTIVE));
        $this->assertFalse(SubscriptionState::canTransition('invalid_status', SubscriptionState::STATUS_ACTIVE));
    }
}
