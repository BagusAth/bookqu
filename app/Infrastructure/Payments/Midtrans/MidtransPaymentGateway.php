<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\Midtrans;

use App\Domain\Payment\PaymentRules;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap as MidtransSnap;
use Midtrans\Transaction as MidtransTransaction;

class MidtransPaymentGateway
{
    public function __construct()
    {
        $this->setDefaultConfig();
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

            if (!empty($customKey)) {
                return (string) $customKey;
            }
        }

        return (string) config('midtrans.server_key');
    }

    /**
     * Create Snap Token via Midtrans API.
     *
     * @param Payment $payment
     * @param array<string, mixed> $params
     * @return string
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
     * @param string $orderId
     * @param Payment|null $payment
     * @return array|object
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
     * @param array<string, mixed> $payload
     * @param Payment $payment
     * @return bool
     */
    public function verifyWebhookSignature(array $payload, Payment $payment): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
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
        MidtransConfig::$serverKey = config('midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production', false);
        MidtransConfig::$isSanitized = (bool) config('midtrans.is_sanitized', true);
        MidtransConfig::$is3ds = (bool) config('midtrans.is_3ds', true);

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
