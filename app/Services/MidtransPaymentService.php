<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Payment\CheckPaymentStatus;
use App\Actions\Payment\ExpirePayment;
use App\Actions\Payment\SynchronizePaymentStatus;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Models\Payment;
use App\Traits\ClearsBookingCache;

class MidtransPaymentService
{
    use ClearsBookingCache;

    protected MidtransPaymentGateway $gateway;
    protected SynchronizePaymentStatus $syncAction;
    protected CheckPaymentStatus $checkAction;
    protected ExpirePayment $expireAction;

    public function __construct(
        ?MidtransPaymentGateway $gateway = null,
        ?SynchronizePaymentStatus $syncAction = null,
        ?CheckPaymentStatus $checkAction = null,
        ?ExpirePayment $expireAction = null
    ) {
        $this->gateway      = $gateway ?? app(MidtransPaymentGateway::class);
        $this->syncAction   = $syncAction ?? app(SynchronizePaymentStatus::class);
        $this->checkAction  = $checkAction ?? app(CheckPaymentStatus::class);
        $this->expireAction = $expireAction ?? app(ExpirePayment::class);
    }

    /**
     * Configure Midtrans environment and keys for a specific payment.
     */
    public function configureForPayment(Payment $payment): void
    {
        $this->gateway->configureForPayment($payment);
    }

    /**
     * Perform server-side verification to Midtrans API and synchronize status.
     *
     * @param Payment $payment
     * @return array<string, mixed>
     */
    public function verifyAndSync(Payment $payment): array
    {
        return $this->checkAction->execute($payment);
    }

    /**
     * Synchronize Midtrans transaction payload to Payment & Booking / Subscription.
     *
     * @param Payment $payment
     * @param array|object $midtransPayload
     * @return array<string, mixed>
     */
    public function syncStatus(Payment $payment, array|object $midtransPayload): array
    {
        return $this->syncAction->syncStatus($payment, $midtransPayload);
    }

    /**
     * Process success status idempotently.
     *
     * @param Payment $payment
     * @param string|null $paymentType
     * @param string|null $transactionStatus
     * @return array<string, mixed>
     */
    public function processSuccess(
        Payment $payment,
        ?string $paymentType = null,
        ?string $transactionStatus = 'settlement'
    ): array {
        return $this->syncAction->processSuccess($payment, $paymentType, $transactionStatus);
    }

    /**
     * Process failed / cancelled status idempotently.
     *
     * @param Payment $payment
     * @param string|null $paymentType
     * @param string|null $transactionStatus
     * @return array<string, mixed>
     */
    public function processFailed(
        Payment $payment,
        ?string $paymentType = null,
        ?string $transactionStatus = 'cancel'
    ): array {
        return $this->syncAction->processFailed($payment, $paymentType, $transactionStatus);
    }

    /**
     * Atomically expire payment and cancel associated bookings.
     *
     * @param Payment $payment
     * @return array<string, mixed>
     */
    public function expirePayment(Payment $payment): array
    {
        return $this->expireAction->execute($payment);
    }
}
