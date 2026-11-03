<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Domain\Payment\ProviderOutcome;
use App\Infrastructure\Payments\SingaPay\SingaPayPaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessSingaPayWebhook
{
    public function __construct(
        protected SingaPayPaymentGateway $gateway,
        protected PaymentOutcomeIntake $intake
    ) {}

    /**
     * Process incoming SingaPay Money In / Payment Link webhook notification.
     *
     * @return array{success: bool, code: int, message: string}
     */
    public function execute(Request $request): array
    {
        // 1. Verify JSON body structure
        $rawBody = (string) $request->getContent();
        $payload = json_decode($rawBody, true);

        if (! is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('ProcessSingaPayWebhook: Malformed JSON body received');

            return [
                'success' => false,
                'code' => 400,
                'message' => 'Malformed JSON body',
            ];
        }

        // 2. Replay Protection: Validate Webhook Timestamp
        if (! $this->gateway->verifyTimestamp($request)) {
            Log::warning('ProcessSingaPayWebhook: Webhook rejected due to invalid or expired timestamp', [
                'timestamp' => $request->header('X-Timestamp'),
                'ip' => $request->ip(),
            ]);

            return [
                'success' => false,
                'code' => 401,
                'message' => 'Invalid or expired timestamp',
            ];
        }

        // 3. Verify HMAC Signature
        if (! $this->gateway->verifyWebhook($request)) {
            Log::warning('ProcessSingaPayWebhook: Invalid HMAC signature', [
                'uri' => $request->getRequestUri(),
                'ip' => $request->ip(),
            ]);

            return [
                'success' => false,
                'code' => 401,
                'message' => 'Invalid signature',
            ];
        }

        // 4. Translate and hand over: the intake validates payment, provider, tenant,
        // amount and currency once and the Payment lifecycle applies the result.
        $outcome = $this->gateway->outcomeFromNotification($payload);
        $intake = $this->intake->receive($outcome);

        if (! $intake->accepted) {
            return WebhookResponse::forRejection($intake->rejection);
        }

        if ($outcome->result === ProviderOutcome::RESULT_UNKNOWN) {
            Log::warning('ProcessSingaPayWebhook: Missing or unknown transaction status', [
                'payment_id' => $intake->payment?->id,
                'event' => $payload['event'] ?? 'unknown',
            ]);

            return [
                'success' => false,
                'code' => 422,
                'message' => 'Missing or unknown SingaPay transaction status',
            ];
        }

        return WebhookResponse::forAccepted($intake);
    }
}
