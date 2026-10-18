<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Service;

interface PaymentGatewayInterface
{
    /**
     * Get the identifier name of the payment gateway driver.
     * e.g. 'singapay' or 'midtrans'
     */
    public function getDriverName(): string;

    /**
     * Create payment link or token for a customer booking.
     *
     * @param Payment $payment
     * @param Service $service
     * @param array<string, mixed> $customerData
     * @param int $slotCount
     * @return array{payment_url?: ?string, snap_token?: ?string, external_id?: ?string}
     */
    public function createBookingPayment(
        Payment $payment,
        Service $service,
        array $customerData,
        int $slotCount = 1
    ): array;

    /**
     * Create payment link or token for an owner subscription plan checkout.
     *
     * @param Payment $payment
     * @param Plan $plan
     * @param array<string, mixed> $payerData
     * @param float $biayaPlatform
     * @return array{payment_url?: ?string, snap_token?: ?string, external_id?: ?string}
     */
    public function createSubscriptionPayment(
        Payment $payment,
        Plan $plan,
        array $payerData,
        float $biayaPlatform = 0.0
    ): array;

    /**
     * Check transaction status with the payment gateway.
     *
     * @param Payment $payment
     * @return array<string, mixed>
     */
    public function checkTransactionStatus(Payment $payment): array;
}
