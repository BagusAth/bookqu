<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentState;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class CheckPaymentStatus
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected ?MidtransPaymentGateway $gateway = null,
        protected ?SynchronizePaymentStatus $syncAction = null
    ) {
        $this->gateway    = $gateway ?? app(MidtransPaymentGateway::class);
        $this->syncAction = $syncAction ?? app(SynchronizePaymentStatus::class);
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

        // Delegate status check to the appropriate gateway driver
        $driver = $this->gatewayManager->forPayment($payment);

        return $driver->checkTransactionStatus($payment);
    }
}
