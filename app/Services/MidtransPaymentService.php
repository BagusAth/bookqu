<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Payment\CheckPaymentStatus;
use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Payment\SynchronizePaymentStatus;
use App\Domain\Payment\PaymentTransition;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Models\Payment;
use App\Traits\ClearsBookingCache;

class MidtransPaymentService
{
    use ClearsBookingCache;

    protected MidtransPaymentGateway $gateway;

    protected SynchronizePaymentStatus $syncAction;

    protected CheckPaymentStatus $checkAction;

    protected PaymentLifecycle $lifecycle;

    public function __construct(
        ?MidtransPaymentGateway $gateway = null,
        ?SynchronizePaymentStatus $syncAction = null,
        ?CheckPaymentStatus $checkAction = null,
        ?PaymentLifecycle $lifecycle = null
    ) {
        $this->gateway = $gateway ?? app(MidtransPaymentGateway::class);
        $this->syncAction = $syncAction ?? app(SynchronizePaymentStatus::class);
        $this->checkAction = $checkAction ?? app(CheckPaymentStatus::class);
        $this->lifecycle = $lifecycle ?? app(PaymentLifecycle::class);
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
     * @return array<string, mixed>
     */
    public function verifyAndSync(Payment $payment): array
    {
        return $this->checkAction->execute($payment);
    }

    /**
     * Synchronize Midtrans transaction payload to Payment & Booking / Subscription.
     *
     * @return array<string, mixed>
     */
    public function syncStatus(Payment $payment, array|object $midtransPayload): array
    {
        return $this->syncAction->syncStatus($payment, $midtransPayload);
    }

    /**
     * Process success status idempotently.
     *
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
     * @return array<string, mixed>
     */
    public function expirePayment(Payment $payment): array
    {
        $result = $this->lifecycle->transition($payment, PaymentTransition::Expired);

        return [
            'status' => $result->status(),
            'transaction_status' => 'expire',
            'message' => $result->changed()
                ? 'Payment kadaluarsa dan booking berhasil dibatalkan.'
                : 'Payment sudah final, kedaluwarsa diabaikan.',
            'payment' => $result->payment,
        ];
    }
}
