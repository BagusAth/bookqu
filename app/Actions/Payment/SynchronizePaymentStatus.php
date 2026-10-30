<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Domain\Payment\PaymentTransitionOutcome;
use App\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Log;

/**
 * Maps a provider-reported transaction status to a Payment lifecycle request.
 */
class SynchronizePaymentStatus
{
    public function __construct(
        protected PaymentLifecycle $lifecycle
    ) {}

    /**
     * Single Source of Truth for synchronizing Midtrans transaction status to Payment & Booking / Subscription.
     * Accepts array (from webhook) or object (from MidtransTransaction::status).
     *
     * @return array<string, mixed>
     */
    public function syncStatus(Payment $payment, array|object $midtransPayload): array
    {
        if ($payment->idtenant && ! app(TenantContext::class)->hasTenant()) {
            app(TenantContext::class)->setTenantId($payment->idtenant);
        }

        $payload = is_object($midtransPayload) ? (array) $midtransPayload : $midtransPayload;

        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? null;

        Log::info('SynchronizePaymentStatus processing:', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'payment_type' => $paymentType,
        ]);

        $domainStatus = PaymentState::mapTransactionStatus($transactionStatus, $fraudStatus);

        if ($domainStatus === PaymentState::STATUS_SUKSES) {
            return $this->processSuccess($payment, $paymentType, $transactionStatus);
        }

        if ($domainStatus === PaymentState::STATUS_GAGAL) {
            return $this->processFailed($payment, $paymentType, $transactionStatus);
        }

        if ($domainStatus === PaymentState::STATUS_PENDING) {
            $customMessage = ($transactionStatus === 'capture' && $fraudStatus === 'challenge')
                ? 'Pembayaran sedang dalam review fraud.'
                : null;

            return $this->processPending($payment, $paymentType, $transactionStatus, $customMessage);
        }

        Log::warning('SynchronizePaymentStatus: Unknown transaction status encountered', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'transaction_status' => $transactionStatus,
        ]);

        return [
            'status' => 'unknown',
            'transaction_status' => $transactionStatus,
            'message' => 'Status transaksi tidak dikenal.',
            'payment' => $payment,
        ];
    }

    /**
     * Apply a provider-reported success through the Payment lifecycle module.
     *
     * @return array<string, mixed>
     */
    public function processSuccess(
        Payment $payment,
        ?string $paymentType = null,
        ?string $transactionStatus = 'settlement'
    ): array {
        $result = $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded, $paymentType);

        $message = match ($result->outcome) {
            PaymentTransitionOutcome::Changed => 'Pembayaran berhasil dikonfirmasi.',
            PaymentTransitionOutcome::RefundOwed => 'Pembayaran diterima setelah pesanan dibatalkan atau kedaluwarsa. Dana akan dikembalikan.',
            default => $result->status() === PaymentState::STATUS_SUKSES
                ? 'Pembayaran sudah dikonfirmasi sebelumnya.'
                : 'Pembayaran sudah gagal atau dibatalkan sebelumnya dan tidak dapat diubah.',
        };

        return [
            'status' => $result->status(),
            'transaction_status' => $transactionStatus,
            'message' => $message,
            'payment' => $result->payment,
        ];
    }

    /**
     * Apply a provider-reported failure, denial, expiry or cancellation through the Payment lifecycle module.
     *
     * @return array<string, mixed>
     */
    public function processFailed(
        Payment $payment,
        ?string $paymentType = null,
        ?string $transactionStatus = 'cancel'
    ): array {
        $result = $this->lifecycle->transition($payment, PaymentTransition::ProviderFailed, $paymentType);

        return [
            'status' => $result->status(),
            'transaction_status' => $transactionStatus,
            'message' => $result->changed()
                ? 'Pembayaran gagal atau dibatalkan.'
                : 'Pembayaran gagal/batal atau sudah selesai.',
            'payment' => $result->payment,
        ];
    }

    /**
     * Process pending status idempotently.
     *
     * @return array<string, mixed>
     */
    public function processPending(
        Payment $payment,
        ?string $paymentType,
        ?string $transactionStatus,
        ?string $customMessage = null
    ): array {
        if ($paymentType && $payment->metode !== $paymentType) {
            $payment->update([
                'metode' => $paymentType,
            ]);
        }

        return [
            'status' => PaymentState::STATUS_PENDING,
            'transaction_status' => $transactionStatus,
            'message' => $customMessage ?? 'Pembayaran belum diselesaikan. Silakan selesaikan pembayaran sesuai instruksi.',
            'payment' => $payment,
        ];
    }
}
