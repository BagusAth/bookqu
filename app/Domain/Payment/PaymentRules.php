<?php

declare(strict_types=1);

namespace App\Domain\Payment;

use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Support\Carbon;

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
     * Minutes a new payment of this purpose stays payable (ADR-0009 decision 1).
     */
    public static function deadlineMinutes(string $tipe): int
    {
        return $tipe === PaymentState::TIPE_SUBSCRIPTION
            ? (int) config('payment.deadline.subscription_minutes', 60)
            : (int) config('payment.deadline.booking_minutes', 15);
    }

    /**
     * The deadline for a payment created now; stored in `payments.expired_at` and
     * used for the provider link.
     */
    public static function deadlineFor(string $tipe): Carbon
    {
        return now()->addMinutes(self::deadlineMinutes($tipe));
    }

    /**
     * Grace after the deadline for a provider success already in flight.
     */
    public static function settlementMarginMinutes(): int
    {
        return (int) config('payment.deadline.settlement_margin_minutes', 2);
    }

    /**
     * Whole minutes left until the payment's deadline, at least one (for provider links).
     */
    public static function minutesUntilDeadline(Payment $payment): int
    {
        $deadline = $payment->expired_at ?? self::deadlineFor((string) $payment->tipe);

        return max(1, (int) ceil(now()->diffInSeconds($deadline, false) / 60));
    }

    /**
     * A pending payment past its deadline plus margin may be reclaimed (ADR-0009 decision 3).
     */
    public static function isReclaimable(Payment $payment): bool
    {
        return $payment->status === PaymentState::STATUS_PENDING
            && $payment->expired_at !== null
            && Carbon::parse($payment->expired_at)->addMinutes(self::settlementMarginMinutes())->isPast();
    }

    /**
     * The provider that owns a payment: the only ownership rule (ADR-0007 decision 2).
     * Legacy rows without `provider` are inferred from `metode`; the migration
     * 2026_10_31_000001 backfills them.
     *
     * @return string `singapay`, `midtrans` or `manual` (free, cash, walk-in)
     */
    public static function providerOf(Payment $payment): string
    {
        $provider = strtolower(trim((string) $payment->provider));

        if ($provider !== '') {
            return $provider;
        }

        return match (strtolower(trim((string) $payment->metode))) {
            PaymentState::METODE_SINGAPAY => PaymentState::METODE_SINGAPAY,
            PaymentState::METODE_GRATIS, PaymentState::METODE_CASH => 'manual',
            default => PaymentState::METODE_MIDTRANS,
        };
    }

    /**
     * Whether a reported amount equals the stored amount to the rupiah.
     */
    public static function amountMatches(Payment $payment, string $reportedAmount): bool
    {
        if (! is_numeric($reportedAmount)) {
            return false;
        }

        return (int) round((float) $reportedAmount * 100) === (int) round((float) $payment->jumlah * 100);
    }

    /**
     * Check if a payment is still in a state where it can be processed.
     */
    public static function canProcess(Payment $payment): bool
    {
        return $payment->status === PaymentState::STATUS_PENDING && ! self::isExpired($payment);
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
        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

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
            'plan_price' => $planPrice,
            'platform_fee' => $platformFee,
            'total' => $total,
        ];
    }
}
