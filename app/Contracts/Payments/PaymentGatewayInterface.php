<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\Domain\Payment\ProviderOutcome;
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
     * @param  array<string, mixed>  $customerData
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
     * @param  array<string, mixed>  $payerData
     * @return array{payment_url?: ?string, snap_token?: ?string, external_id?: ?string}
     */
    public function createSubscriptionPayment(
        Payment $payment,
        Plan $plan,
        array $payerData,
        float $biayaPlatform = 0.0
    ): array;

    /**
     * Ask the provider for the payment's current status (ADR-0007 decision 4). Never
     * answers from the local database; an unreachable provider or a provider without
     * a status API yields an `unknown` outcome, never `gagal`.
     */
    public function queryOutcome(Payment $payment): ProviderOutcome;

    /**
     * Best-effort cancellation of the payment's open checkout at the provider, called
     * after the Payment lifecycle committed a payer cancel, expiry or owner settlement
     * (ADR-0007 decision 6). Unsupported providers do nothing; failures may throw and
     * are logged by the caller.
     */
    public function cancel(Payment $payment): void;
}
