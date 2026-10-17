<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentState;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class CreateSubscriptionPayment
{
    public function __construct(
        protected MidtransPaymentGateway $gateway
    ) {
    }

    /**
     * Create subscription payment record and request Snap token from gateway.
     *
     * @param Tenant $tenant
     * @param Plan $plan
     * @param array{
     *     nama_pembayar: string,
     *     email_pembayar: string,
     *     hp_pembayar: string,
     *     catatan?: ?string
     * } $payerData
     * @param float $biayaPlatform
     * @return Payment
     * @throws \Exception
     */
    public function execute(
        Tenant $tenant,
        Plan $plan,
        array $payerData,
        float $biayaPlatform = 0.0
    ): Payment {
        $totalBayar = (float) $plan->hargabulanan + $biayaPlatform;
        $orderId    = $this->generateOrderId();

        /** @var Payment $payment */
        $payment = Payment::create([
            'idtenant'       => $tenant->id,
            'idplan'         => $plan->id,
            'tipe'           => PaymentState::TIPE_SUBSCRIPTION,
            'jumlah'         => $totalBayar,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_MIDTRANS,
            'order_id'       => $orderId,
            'expired_at'     => now()->addHour(),
            'nama_pembayar'  => $payerData['nama_pembayar'],
            'email_pembayar' => $payerData['email_pembayar'],
            'hp_pembayar'    => $payerData['hp_pembayar'],
            'catatan'        => $payerData['catatan'] ?? null,
        ]);

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $totalBayar,
            ],
            'customer_details' => [
                'first_name' => $payerData['nama_pembayar'],
                'email'      => $payerData['email_pembayar'],
                'phone'      => $payerData['hp_pembayar'],
            ],
            'item_details' => [
                [
                    'id'       => 'PLAN-' . $plan->id,
                    'price'    => (int) $plan->hargabulanan,
                    'quantity' => 1,
                    'name'     => 'Subscription Plan ' . ucfirst((string) $plan->namapaket) . ' (1 Bulan)',
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'hour',
                'duration'   => 1,
            ],
        ];

        if ($biayaPlatform > 0) {
            $params['item_details'][] = [
                'id'       => 'PLATFORM-FEE',
                'price'    => (int) $biayaPlatform,
                'quantity' => 1,
                'name'     => 'Biaya Layanan Platform',
            ];
        }

        try {
            $snapToken = $this->gateway->createSnapToken($payment, $params);
            $payment->update(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Error (Subscription): ' . $e->getMessage());
            $payment->update(['status' => PaymentState::STATUS_GAGAL]);
            throw $e;
        }

        return $payment;
    }

    /**
     * Generate sequential daily order ID for subscription payments.
     */
    public function generateOrderId(): string
    {
        $prefix = 'BQ-' . now()->format('Ymd') . '-';
        $lastPayment = Payment::where('order_id', 'like', $prefix . '%')
            ->orderByDesc('order_id')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->order_id, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
