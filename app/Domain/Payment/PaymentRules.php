<?php

declare(strict_types=1);

namespace App\Domain\Payment;

use App\Models\Payment;
use App\Models\Plan;

class PaymentRules
{
    /**
     * Check if a payment has expired based on its expired_at timestamp.
     */
    public static function isExpired(Payment $payment): bool
    {
        return $payment->expired_at !== null && $payment->expired_at->isPast();
    }

    /**
     * Check if a payment is still in a state where it can be processed.
     */
    public static function canProcess(Payment $payment): bool
    {
        return $payment->status === PaymentState::STATUS_PENDING && !self::isExpired($payment);
    }

    /**
     * Verify Midtrans webhook SHA-512 signature.
     */
    public static function verifySignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $serverKey,
        string $signatureKey
    ): bool {
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expectedSignature, $signatureKey);
    }

    /**
     * Generate unique BookQu Order ID with standard prefix.
     */
    public static function generateOrderId(string $prefix, int $tenantId): string
    {
        return sprintf('%s-%d-%d-%d', $prefix, $tenantId, time(), random_int(100, 999));
    }

    /**
     * Calculate subscription total amount including platform fee.
     *
     * @return array{plan_price: float, platform_fee: float, total: float}
     */
    public static function calculateSubscriptionPrice(Plan $plan, float $platformFee = 0.0): array
    {
        $planPrice = (float) $plan->hargabulanan;
        $total = max(0, $planPrice + $platformFee);

        return [
            'plan_price'   => $planPrice,
            'platform_fee' => $platformFee,
            'total'        => $total,
        ];
    }
}
