<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentState;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class CheckPaymentStatus
{
    public function __construct(
        protected MidtransPaymentGateway $gateway,
        protected SynchronizePaymentStatus $syncAction
    ) {
    }

    /**
     * Perform server-side verification to payment gateway and synchronize result to BookQu database.
     *
     * @param Payment $payment
     * @return array<string, mixed>
     */
    public function execute(Payment $payment): array
    {
        if (!$payment->order_id) {
            Log::warning('CheckPaymentStatus: Payment does not have order_id', ['payment_id' => $payment->id]);

            return [
                'status'  => $payment->status,
                'message' => 'Order ID tidak ditemukan.',
                'payment' => $payment,
            ];
        }

        // Fast-path: if payment already marked as successful in database, return immediately
        if ($payment->status === PaymentState::STATUS_SUKSES) {
            return [
                'status'             => PaymentState::STATUS_SUKSES,
                'transaction_status' => 'settlement',
                'message'            => 'Pembayaran berhasil dikonfirmasi.',
                'payment'            => $payment,
            ];
        }

        try {
            $midtransStatus = $this->gateway->getTransactionStatus($payment->order_id, $payment);

            return $this->syncAction->syncStatus($payment, $midtransStatus);
        } catch (\Throwable $e) {
            Log::error('CheckPaymentStatus Error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'order_id'   => $payment->order_id,
            ]);

            return [
                'status'  => 'error',
                'message' => 'Gagal memverifikasi status transaksi ke Midtrans: ' . $e->getMessage(),
                'payment' => $payment,
            ];
        }
    }
}
