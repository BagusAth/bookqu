<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhook
{
    public function __construct(
        protected MidtransPaymentGateway $gateway,
        protected SynchronizePaymentStatus $syncAction
    ) {
    }

    /**
     * Process incoming payment webhook notification payload.
     *
     * @param array<string, mixed> $payload
     * @return array{success: bool, code: int, message: string}
     */
    public function execute(array $payload): array
    {
        Log::info('ProcessPaymentWebhook Received:', $payload);

        $orderId      = $payload['order_id'] ?? null;
        $statusCode   = $payload['status_code'] ?? null;
        $grossAmount  = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            Log::warning('ProcessPaymentWebhook: Missing required fields', $payload);

            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Missing required fields',
            ];
        }

        /** @var Payment|null $payment */
        $payment = Payment::withoutGlobalScope(TenantScope::class)
            ->where('order_id', $orderId)
            ->first();

        if (!$payment) {
            Log::warning('ProcessPaymentWebhook: Payment not found', ['order_id' => $orderId]);

            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Payment not found',
            ];
        }

        // Validate webhook signature
        if (!$this->gateway->verifyWebhookSignature($payload, $payment)) {
            Log::warning('ProcessPaymentWebhook: Signature mismatch', [
                'order_id' => $orderId,
            ]);

            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Invalid signature',
            ];
        }

        app(TenantContext::class)->setTenantId($payment->idtenant);

        try {
            $this->syncAction->syncStatus($payment, $payload);
        } finally {
            app(TenantContext::class)->clear();
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'OK',
        ];
    }
}
