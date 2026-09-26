<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Models\Payment;
use App\Models\Plan;
use Carbon\Carbon;
use Tests\TestCase;

class PaymentRulesTest extends TestCase
{
    public function test_is_expired(): void
    {
        $expiredPayment = new Payment([
            'status'     => PaymentState::STATUS_PENDING,
            'expired_at' => Carbon::now()->subMinutes(10),
        ]);

        $activePayment = new Payment([
            'status'     => PaymentState::STATUS_PENDING,
            'expired_at' => Carbon::now()->addMinutes(10),
        ]);

        $nullExpiredPayment = new Payment([
            'status'     => PaymentState::STATUS_PENDING,
            'expired_at' => null,
        ]);

        $this->assertTrue(PaymentRules::isExpired($expiredPayment));
        $this->assertFalse(PaymentRules::isExpired($activePayment));
        $this->assertFalse(PaymentRules::isExpired($nullExpiredPayment));
    }

    public function test_can_process(): void
    {
        $pendingActive = new Payment([
            'status'     => PaymentState::STATUS_PENDING,
            'expired_at' => Carbon::now()->addMinutes(15),
        ]);

        $pendingExpired = new Payment([
            'status'     => PaymentState::STATUS_PENDING,
            'expired_at' => Carbon::now()->subMinutes(5),
        ]);

        $alreadySuccess = new Payment([
            'status'     => PaymentState::STATUS_SUKSES,
            'expired_at' => Carbon::now()->addMinutes(15),
        ]);

        $this->assertTrue(PaymentRules::canProcess($pendingActive));
        $this->assertFalse(PaymentRules::canProcess($pendingExpired));
        $this->assertFalse(PaymentRules::canProcess($alreadySuccess));
    }

    public function test_verify_signature(): void
    {
        $orderId = 'ORDER-12345';
        $statusCode = '200';
        $grossAmount = '150000.00';
        $serverKey = 'SB-Mid-server-secret123';

        $expectedHash = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $this->assertTrue(PaymentRules::verifySignature($orderId, $statusCode, $grossAmount, $serverKey, $expectedHash));
        $this->assertFalse(PaymentRules::verifySignature($orderId, $statusCode, $grossAmount, $serverKey, 'invalid_hash'));
        $this->assertFalse(PaymentRules::verifySignature($orderId, '400', $grossAmount, $serverKey, $expectedHash));
    }

    public function test_generate_order_id(): void
    {
        $orderId = PaymentRules::generateOrderId('BKG', 42);

        $this->assertStringStartsWith('BKG-42-', $orderId);
    }

    public function test_calculate_subscription_price(): void
    {
        $plan = new Plan(['hargabulanan' => 150000]);

        $pricing = PaymentRules::calculateSubscriptionPrice($plan, 5000.0);

        $this->assertEquals(150000.0, $pricing['plan_price']);
        $this->assertEquals(5000.0, $pricing['platform_fee']);
        $this->assertEquals(155000.0, $pricing['total']);
    }
}
