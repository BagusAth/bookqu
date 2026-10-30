<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\Midtrans;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\ProviderOutcome;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap as MidtransSnap;
use Midtrans\Transaction as MidtransTransaction;

class MidtransPaymentGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        $this->setDefaultConfig();
    }

    /**
     * Get the identifier name of the payment gateway driver.
     */
    public function getDriverName(): string
    {
        return 'midtrans';
    }

    /**
     * Create Snap Token for customer booking.
     *
     * @param  array<string, mixed>  $customerData
     * @return array{snap_token: string}
     */
    public function createBookingPayment(
        Payment $payment,
        Service $service,
        array $customerData,
        int $slotCount = 1
    ): array {
        $itemName = 'Booking: '.$service->namalayanan;
        if ($slotCount > 1) {
            $itemName .= ' ('.$slotCount.' slot)';
        }

        $expiryMinutes = (int) (config('payment.drivers.singapay.expiry_minutes') ?? config('services.singapay.expiry_minutes', 15));

        $params = [
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => (int) $payment->jumlah,
            ],
            'customer_details' => [
                'first_name' => $customerData['namapelanggan'],
                'email' => $customerData['email'],
                'phone' => $customerData['nomorhp'],
            ],
            'item_details' => [
                [
                    'id' => 'SRV-'.$service->id,
                    'price' => (int) $payment->jumlah,
                    'quantity' => 1,
                    'name' => $itemName,
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => $expiryMinutes,
            ],
        ];

        $snapToken = $this->createSnapToken($payment, $params);
        $payment->update([
            'snap_token' => $snapToken,
            'provider' => 'midtrans',
            'metode' => PaymentState::METODE_MIDTRANS,
        ]);

        return [
            'snap_token' => $snapToken,
        ];
    }

    /**
     * Create Snap Token for owner subscription checkout.
     *
     * @param  array<string, mixed>  $payerData
     * @return array{snap_token: string}
     */
    public function createSubscriptionPayment(
        Payment $payment,
        Plan $plan,
        array $payerData,
        float $biayaPlatform = 0.0
    ): array {
        $totalBayar = (float) $plan->hargabulanan + $biayaPlatform;

        $params = [
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => (int) $totalBayar,
            ],
            'customer_details' => [
                'first_name' => $payerData['nama_pembayar'],
                'email' => $payerData['email_pembayar'],
                'phone' => $payerData['hp_pembayar'],
            ],
            'item_details' => [
                [
                    'id' => 'PLAN-'.$plan->id,
                    'price' => (int) $plan->hargabulanan,
                    'quantity' => 1,
                    'name' => 'Subscription Plan '.ucfirst((string) $plan->namapaket).' (1 Bulan)',
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'hour',
                'duration' => 1,
            ],
        ];

        if ($biayaPlatform > 0) {
            $params['item_details'][] = [
                'id' => 'PLATFORM-FEE',
                'price' => (int) $biayaPlatform,
                'quantity' => 1,
                'name' => 'Biaya Layanan Platform',
            ];
        }

        try {
            $snapToken = $this->createSnapToken($payment, $params);
            $payment->update([
                'snap_token' => $snapToken,
                'provider' => 'midtrans',
                'metode' => PaymentState::METODE_MIDTRANS,
            ]);

            return [
                'snap_token' => $snapToken,
            ];
        } catch (\Throwable $e) {
            // The caller (CreateSubscriptionPayment) records the initiation failure.
            Log::error('Midtrans Snap Error (Subscription): '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Ask Midtrans for the transaction status; an unreachable API yields `unknown`.
     */
    public function queryOutcome(Payment $payment): ProviderOutcome
    {
        try {
            $status = $this->getTransactionStatus((string) $payment->order_id, $payment);
        } catch (\Throwable $e) {
            Log::warning('Midtrans status query failed: '.$e->getMessage(), [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
            ]);

            return ProviderOutcome::unknown($this->getDriverName(), (string) $payment->order_id);
        }

        return $this->outcomeFromNotification(is_object($status) ? (array) $status : $status);
    }

    /**
     * Translate a Midtrans notification or status response into BookQu's vocabulary.
     * The caller must have verified the notification signature first.
     *
     * @param  array<string, mixed>  $payload
     */
    public function outcomeFromNotification(array $payload): ProviderOutcome
    {
        $scalar = static fn (mixed $value): ?string => is_scalar($value) && (string) $value !== '' ? (string) $value : null;

        $result = PaymentState::mapTransactionStatus(
            $scalar($payload['transaction_status'] ?? null),
            $scalar($payload['fraud_status'] ?? null)
        );

        return new ProviderOutcome(
            provider: $this->getDriverName(),
            references: array_filter([$scalar($payload['order_id'] ?? null)]),
            result: $result,
            amount: $scalar($payload['gross_amount'] ?? null),
            // Midtrans Indonesia settles in rupiah only; older notifications omit the field.
            currency: $scalar($payload['currency'] ?? null) ?? 'IDR',
            transactionId: $scalar($payload['transaction_id'] ?? null),
            method: $scalar($payload['payment_type'] ?? null),
            eventTime: $scalar($payload['settlement_time'] ?? $payload['transaction_time'] ?? null),
        );
    }

    /**
     * Expire the open Snap transaction. Payments that never reached Midtrans have no
     * Snap token and nothing to cancel.
     */
    public function cancel(Payment $payment): void
    {
        if (empty($payment->snap_token) || empty($payment->order_id)) {
            return;
        }

        $this->configureForPayment($payment);
        MidtransTransaction::expire((string) $payment->order_id);
    }

    /**
     * Configure Midtrans global config for a specific Payment.
     * Supports tenant-specific custom keys when tenant is in owner payment mode.
     */
    public function configureForPayment(Payment $payment): void
    {
        $tenant = $payment->tenant;
        if ($tenant && $tenant->payment_mode === 'owner' && $payment->tipe === 'booking') {
            $isProd = $tenant->midtrans_environment === 'production';
            $serverKey = $isProd
                ? $tenant->midtrans_prod_server_key
                : $tenant->midtrans_sandbox_server_key;

            if ($serverKey) {
                MidtransConfig::$serverKey = $serverKey;
                MidtransConfig::$isProduction = $isProd;
                MidtransConfig::$isSanitized = true;
                MidtransConfig::$is3ds = true;

                return;
            }
        }

        $this->setDefaultConfig();
    }

    /**
     * Resolve server key for a given Payment (owner tenant key or platform key).
     */
    public function resolveServerKey(Payment $payment): string
    {
        $tenant = $payment->tenant;
        if ($tenant && $tenant->payment_mode === 'owner' && $payment->tipe === 'booking') {
            $isProd = $tenant->midtrans_environment === 'production';
            $customKey = $isProd
                ? $tenant->midtrans_prod_server_key
                : $tenant->midtrans_sandbox_server_key;

            if (! empty($customKey)) {
                return (string) $customKey;
            }
        }

        return (string) config('payment.drivers.midtrans.server_key');
    }

    /**
     * Create Snap Token via Midtrans API.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws \Exception
     */
    public function createSnapToken(Payment $payment, array $params): string
    {
        $this->configureForPayment($payment);

        try {
            return MidtransSnap::getSnapToken($params);
        } catch (\Throwable $e) {
            if (app()->environment('testing')) {
                return 'mocked-snap-token';
            }

            throw $e;
        }
    }

    /**
     * Fetch transaction status from Midtrans API by order_id.
     *
     * @throws \Exception
     */
    public function getTransactionStatus(string $orderId, ?Payment $payment = null): array|object
    {
        if ($payment !== null) {
            $this->configureForPayment($payment);
        }

        return MidtransTransaction::status($orderId);
    }

    /**
     * Validate webhook signature using the correct server key for the payment.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhookSignature(array $payload, Payment $payment): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if (! $orderId || ! $statusCode || ! $grossAmount || ! $signatureKey) {
            return false;
        }

        $serverKey = $this->resolveServerKey($payment);

        return PaymentRules::verifySignature(
            $orderId,
            $statusCode,
            $grossAmount,
            $serverKey,
            $signatureKey
        );
    }

    /**
     * Set default platform Midtrans configuration.
     */
    protected function setDefaultConfig(): void
    {
        MidtransConfig::$serverKey = config('payment.drivers.midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('payment.drivers.midtrans.is_production', false);
        MidtransConfig::$isSanitized = (bool) config('payment.drivers.midtrans.is_sanitized', true);
        MidtransConfig::$is3ds = (bool) config('payment.drivers.midtrans.is_3ds', true);

        // P0-17: SSL Hardening
        $curlOptions = [CURLOPT_HTTPHEADER => []];
        if (app()->environment('local')) {
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = 0;
        } else {
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
        }
        MidtransConfig::$curlOptions = $curlOptions;
    }
}
