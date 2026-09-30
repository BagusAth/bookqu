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
     * Process incoming SingaPay Money In webhook notification.
     *
     * @param Request $request
     * @return array{success: bool, code: int, message: string}
     */
    public function execute(Request $request): array
    {
        // 1. Verify HMAC Signature
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

        $payload = $request->all();
        Log::info('ProcessSingaPayWebhook received valid notification:', [
            'event'   => $payload['event'] ?? 'unknown',
            'reff_no' => $this->extractReffNo($payload),
        ]);

        // 2. Extract Reference / Order ID
        $reffNo = $this->extractReffNo($payload);

        if (!$reffNo) {
            Log::warning('ProcessSingaPayWebhook: Missing reference number in payload', $payload);

            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Missing reference number',
            ];
        }

        // 3. Find Payment (bypassing tenant global scope)
        /** @var Payment|null $payment */
        $payment = Payment::withoutGlobalScope(TenantScope::class)
            ->where('order_id', $reffNo)
            ->first();

        if (!$payment) {
            $payment = Payment::withoutGlobalScope(TenantScope::class)
                ->where('external_id', $reffNo)
                ->first();
        }

        if (!$payment) {
            Log::warning('ProcessSingaPayWebhook: Payment not found for reference', ['reff_no' => $reffNo]);

            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Payment not found',
            ];
        }

        // 4. Validate Tenant Isolation
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

        // 5. Validate Transaction Amount
        $incomingAmount = $this->extractAmount($payload);

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

        // 6. Idempotency Check
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

        // 7. Synchronize Payment Status
        app(TenantContext::class)->setTenantId($payment->idtenant);

        try {
            $rawStatus    = (string) ($payload['data']['transaction']['status'] ?? $payload['data']['status'] ?? 'paid');
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
     * Extract reference number from SingaPay payload structures.
     *
     * @param array<string, mixed> $payload
     * @return string|null
     */
    protected function extractReffNo(array $payload): ?string
    {
        $reff = $payload['data']['payment']['additional_info']['payment_link']['reff_no']
            ?? $payload['data']['transaction']['reff_no']
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
}
