<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentState;
use App\Infrastructure\Payments\SingaPay\SingaPayPaymentGateway;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessSingaPayWebhook
{
    public function __construct(
        protected SingaPayPaymentGateway $gateway,
        protected SynchronizePaymentStatus $syncAction
    ) {
    }

    /**
     * Process incoming SingaPay Money In / Payment Link webhook notification.
     *
     * @param Request $request
     * @return array{success: bool, code: int, message: string}
     */
    public function execute(Request $request): array
    {
        // 1. Verify JSON body structure
        $rawBody = (string) $request->getContent();
        $payload = json_decode($rawBody, true);

        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('ProcessSingaPayWebhook: Malformed JSON body received');

            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Malformed JSON body',
            ];
        }

        // 2. Replay Protection: Validate Webhook Timestamp
        if (!$this->gateway->verifyTimestamp($request)) {
            Log::warning('ProcessSingaPayWebhook: Webhook rejected due to invalid or expired timestamp', [
                'timestamp' => $request->header('X-Timestamp'),
                'ip'        => $request->ip(),
            ]);

            return [
                'success' => false,
                'code'    => 401,
                'message' => 'Invalid or expired timestamp',
            ];
        }

        // 3. Verify HMAC Signature
        if (!$this->gateway->verifyWebhook($request)) {
            Log::warning('ProcessSingaPayWebhook: Invalid HMAC signature', [
                'uri' => $request->getRequestUri(),
                'ip'  => $request->ip(),
            ]);

            return [
                'success' => false,
                'code'    => 401,
                'message' => 'Invalid signature',
            ];
        }

        // 4. Resolve Payment using data.transaction.reff_no as primary identifier
        $payment = $this->resolvePayment($payload);

        if (!$payment) {
            Log::warning('ProcessSingaPayWebhook: Payment not found for webhook notification', [
                'reff_no'  => $this->extractReffNo($payload),
                'event'    => $payload['event'] ?? 'unknown',
            ]);

            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Payment not found',
            ];
        }

        Log::info('ProcessSingaPayWebhook: Processing verified webhook', [
            'payment_id' => $payment->id,
            'order_id'   => $payment->order_id,
            'event'      => $payload['event'] ?? 'unknown',
        ]);

        // 5. Validate Tenant Isolation
        if (!$payment->idtenant || !$payment->tenant) {
            Log::warning('ProcessSingaPayWebhook: Payment tenant missing or invalid', [
                'payment_id' => $payment->id,
                'idtenant'   => $payment->idtenant,
            ]);

            return [
                'success' => false,
                'code'    => 422,
                'message' => 'Tenant isolation violation',
            ];
        }

        // 6. Validate Transaction Amount & Currency
        $incomingAmount   = $this->extractAmount($payload);
        $incomingCurrency = $this->extractCurrency($payload);

        if ($incomingCurrency !== null && strtoupper((string) $incomingCurrency) !== 'IDR') {
            Log::warning('ProcessSingaPayWebhook: Currency mismatch', [
                'order_id' => $payment->order_id,
                'currency' => $incomingCurrency,
            ]);

            return [
                'success' => false,
                'code'    => 422,
                'message' => 'Transaction currency mismatch',
            ];
        }

        if ($incomingAmount !== null) {
            $expectedAmount = (float) $payment->jumlah;

            if (abs($incomingAmount - $expectedAmount) > 0.01) {
                Log::warning('ProcessSingaPayWebhook: Amount mismatch', [
                    'order_id' => $payment->order_id,
                    'expected' => $expectedAmount,
                    'received' => $incomingAmount,
                ]);

                return [
                    'success' => false,
                    'code'    => 422,
                    'message' => 'Transaction amount mismatch',
                ];
            }
        }

        // 7. Idempotency Check
        if ($payment->status === PaymentState::STATUS_SUKSES) {
            Log::info('ProcessSingaPayWebhook: Duplicate webhook received for already successful payment', [
                'payment_id' => $payment->id,
                'order_id'   => $payment->order_id,
            ]);

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Payment already processed successfully',
            ];
        }

        if ($payment->status === PaymentState::STATUS_GAGAL) {
            Log::info('ProcessSingaPayWebhook: Webhook received for already failed/cancelled payment', [
                'payment_id' => $payment->id,
                'order_id'   => $payment->order_id,
            ]);

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Payment already failed or cancelled',
            ];
        }

        // 8. Synchronize Payment Status
        app(TenantContext::class)->setTenantId($payment->idtenant);

        try {
            $rawStatus = (string) (
                $payload['data']['transaction']['status']
                ?? $payload['data']['status']
                ?? $payload['status']
                ?? 'paid'
            );

            $domainStatus = PaymentState::mapSingaPayStatus($rawStatus);

            if ($domainStatus === PaymentState::STATUS_SUKSES) {
                $this->syncAction->processSuccess($payment, PaymentState::METODE_SINGAPAY, $rawStatus);
            } elseif ($domainStatus === PaymentState::STATUS_GAGAL) {
                $this->syncAction->processFailed($payment, PaymentState::METODE_SINGAPAY, $rawStatus);
            } elseif ($domainStatus === PaymentState::STATUS_PENDING) {
                $this->syncAction->processPending($payment, PaymentState::METODE_SINGAPAY, $rawStatus);
            } else {
                Log::warning('ProcessSingaPayWebhook: Unknown SingaPay status encountered', [
                    'raw_status' => $rawStatus,
                    'order_id'   => $payment->order_id,
                ]);
            }
        } finally {
            app(TenantContext::class)->clear();
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'OK',
        ];
    }

    /**
     * Resolve Payment using data.transaction.reff_no as primary identifier,
     * with fallback to payment-link webhook structures.
     *
     * @param array<string, mixed> $payload
     * @return Payment|null
     */
    protected function resolvePayment(array $payload): ?Payment
    {
        // 1. Primary identifier per specification: data.transaction.reff_no
        $primaryReff = $payload['data']['transaction']['reff_no'] ?? null;
        if (!empty($primaryReff)) {
            $payment = Payment::withoutGlobalScope(TenantScope::class)
                ->where('order_id', (string) $primaryReff)
                ->first();

            if ($payment) {
                return $payment;
            }

            $payment = Payment::withoutGlobalScope(TenantScope::class)
                ->where('external_id', (string) $primaryReff)
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        // 2. Secondary candidate references from payment-link structures
        $secondaryCandidates = array_filter(array_unique([
            $payload['data']['payment']['additional_info']['payment_link']['reff_no'] ?? null,
            $payload['data']['reff_no'] ?? null,
            $payload['data']['payment_link']['reff_no'] ?? null,
            $payload['order_id'] ?? null,
            (string) ($payload['data']['payment']['additional_info']['payment_link']['id'] ?? ''),
            (string) ($payload['data']['id'] ?? ''),
        ]));

        foreach ($secondaryCandidates as $ref) {
            $payment = Payment::withoutGlobalScope(TenantScope::class)
                ->where('order_id', (string) $ref)
                ->first();

            if ($payment) {
                return $payment;
            }

            $payment = Payment::withoutGlobalScope(TenantScope::class)
                ->where('external_id', (string) $ref)
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        return null;
    }

    /**
     * Extract reference number from SingaPay payload structures for logging.
     *
     * @param array<string, mixed> $payload
     * @return string|null
     */
    protected function extractReffNo(array $payload): ?string
    {
        $reff = $payload['data']['transaction']['reff_no']
            ?? $payload['data']['payment']['additional_info']['payment_link']['reff_no']
            ?? $payload['data']['reff_no']
            ?? $payload['order_id']
            ?? null;

        return $reff ? (string) $reff : null;
    }

    /**
     * Extract numeric amount from SingaPay payload structures.
     *
     * @param array<string, mixed> $payload
     * @return float|null
     */
    protected function extractAmount(array $payload): ?float
    {
        $val = $payload['data']['transaction']['amount']['value']
            ?? $payload['data']['amount']['value']
            ?? $payload['data']['payment']['additional_info']['payment_link']['total_amount']
            ?? $payload['data']['total_amount']
            ?? null;

        return $val !== null ? (float) $val : null;
    }

    /**
     * Extract currency string from SingaPay payload structures.
     *
     * @param array<string, mixed> $payload
     * @return string|null
     */
    protected function extractCurrency(array $payload): ?string
    {
        $curr = $payload['data']['transaction']['amount']['currency']
            ?? $payload['data']['amount']['currency']
            ?? null;

        return $curr ? (string) $curr : null;
    }
}
