<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhook
{
    public function __construct(
        protected MidtransPaymentGateway $gateway,
        protected PaymentOutcomeIntake $intake
    ) {}

    /**
     * Process incoming payment webhook notification payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, code: int, message: string}
     */
    public function execute(array $payload): array
    {
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        // Keep operational context but avoid persisting the raw webhook payload,
        // which may contain customer data and the provider signature value.
        $safeLogContext = array_filter([
            'order_id' => is_scalar($orderId) ? (string) $orderId : null,
            'status_code' => is_scalar($statusCode) ? (string) $statusCode : null,
            'gross_amount' => is_scalar($grossAmount) ? (string) $grossAmount : null,
            'transaction_status' => is_scalar($payload['transaction_status'] ?? null)
                ? (string) ($payload['transaction_status'] ?? null)
                : null,
            'payment_type' => is_scalar($payload['payment_type'] ?? null)
                ? (string) ($payload['payment_type'] ?? null)
                : null,
        ], static fn ($value) => $value !== null && $value !== '');

        Log::info('ProcessPaymentWebhook Received', $safeLogContext);

        if (! $orderId || ! $statusCode || ! $grossAmount || ! $signatureKey) {
            Log::warning('ProcessPaymentWebhook: Missing required fields', $safeLogContext);

            return [
                'success' => false,
                'code' => 400,
                'message' => 'Missing required fields',
            ];
        }

        /** @var Payment|null $payment */
        $payment = Payment::withoutGlobalScope(TenantScope::class)
            ->where('order_id', $orderId)
            ->first();

        if (! $payment) {
            Log::warning('ProcessPaymentWebhook: Payment not found', ['order_id' => $orderId]);

            return [
                'success' => false,
                'code' => 404,
                'message' => 'Payment not found',
            ];
        }

        // Do not let a Midtrans callback operate on a record explicitly owned by
        // SingaPay. Keep legacy Midtrans rows resolvable even if their `metode`
        // was replaced by Midtrans's payment-type value after settlement.
        if (PaymentRules::providerOf($payment) !== PaymentState::METODE_MIDTRANS) {
            Log::warning('ProcessPaymentWebhook: Provider mismatch', [
                'payment_id' => $payment->id,
                'order_id' => $orderId,
                'provider' => $payment->provider,
            ]);

            return [
                'success' => false,
                'code' => 404,
                'message' => 'Payment not found',
            ];
        }

        // Validate webhook signature
        if (! $this->gateway->verifyWebhookSignature($payload, $payment)) {
            Log::warning('ProcessPaymentWebhook: Signature mismatch', [
                'order_id' => $orderId,
            ]);

            return [
                'success' => false,
                'code' => 403,
                'message' => 'Invalid signature',
            ];
        }

        // The signature proves the origin; the intake checks amount, currency and
        // tenant and hands the outcome to the Payment lifecycle.
        $intake = $this->intake->receive($this->gateway->outcomeFromNotification($payload));

        return $intake->accepted
            ? WebhookResponse::forAccepted($intake)
            : WebhookResponse::forRejection($intake->rejection);
    }
}
