<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class CreateSubscriptionPayment
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected ?MidtransPaymentGateway $gateway = null
    ) {
        $this->gateway = $gateway ?? app(MidtransPaymentGateway::class);
    }

    /**
     * Create subscription payment record and request payment link or Snap token from gateway.
     *
     * @param array{
     *     nama_pembayar: string,
     *     email_pembayar: string,
     *     hp_pembayar: string,
     *     catatan?: ?string
     * } $payerData
     *
     * @throws \Exception
     */
    public function execute(
        Tenant $tenant,
        Plan $plan,
        array $payerData,
        float $biayaPlatform = 0.0
    ): Payment {
        $totalBayar = (float) $plan->hargabulanan + $biayaPlatform;
        $orderId = $this->generateOrderId();
        $activeDriver = $this->gatewayManager->driver();
        $providerName = $activeDriver->getDriverName();

        /** @var Payment $payment */
        $payment = Payment::create([
            'idtenant' => $tenant->id,
            'idplan' => $plan->id,
            'tipe' => PaymentState::TIPE_SUBSCRIPTION,
            'jumlah' => $totalBayar,
            'status' => PaymentState::STATUS_PENDING,
            'metode' => $providerName,
            'provider' => $providerName,
            'order_id' => $orderId,
            'expired_at' => now()->addHour(),
            'nama_pembayar' => $payerData['nama_pembayar'],
            'email_pembayar' => $payerData['email_pembayar'],
            'hp_pembayar' => $payerData['hp_pembayar'],
            'catatan' => $payerData['catatan'] ?? null,
        ]);

        try {
            $activeDriver->createSubscriptionPayment($payment, $plan, $payerData, $biayaPlatform);
            $payment->refresh();
        } catch (\Throwable $e) {
            Log::error("Subscription Payment Error ({$providerName}): ".$e->getMessage());
            app(PaymentLifecycle::class)->transition($payment, PaymentTransition::InitiationFailed);
            throw $e;
        }

        return $payment;
    }

    /**
     * Generate sequential daily order ID for subscription payments.
     */
    public function generateOrderId(): string
    {
        $prefix = 'BQ-'.now()->format('Ymd').'-';
        $lastPayment = Payment::where('order_id', 'like', $prefix.'%')
            ->orderByDesc('order_id')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->order_id, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
