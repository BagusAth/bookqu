<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Payment\ProcessSingaPayWebhook;
use App\Infrastructure\Payments\SingaPay\SingaPayWebhookVerifier;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

/**
 * Local/dev helper: SingaPay delivers webhooks only to the URL registered in its
 * dashboard, so a local or dev instance never learns that a payment succeeded.
 * This builds a correctly signed SingaPay notification for an existing payment and
 * runs it through the real webhook entry point (BQ-076). Refused in production.
 */
class SimulateSingaPayWebhook extends Command
{
    protected $signature = 'singapay:simulate-webhook
                            {order_id : The payment order id}
                            {--status=paid : SingaPay transaction status to report (paid, failed, expired, cancelled)}';

    protected $description = 'Local/dev only: run a signed SingaPay webhook for a payment through the real webhook flow';

    public function handle(ProcessSingaPayWebhook $processWebhook, SingaPayWebhookVerifier $verifier): int
    {
        if (app()->environment('production')) {
            $this->error('Refused: this command never runs in production.');

            return self::FAILURE;
        }

        $secret = (string) config('services.singapay.client_secret');
        if ($secret === '') {
            $this->error('SINGAPAY_CLIENT_SECRET is not set; the webhook cannot be signed.');

            return self::FAILURE;
        }

        // scope-bypass: a console command has no tenant context; the payment is looked up by its order id only.
        $payment = Payment::withoutGlobalScope(TenantScope::class)->where('order_id', $this->argument('order_id'))->first();
        if ($payment === null) {
            $this->error('Payment not found.');

            return self::FAILURE;
        }

        $endpoint = '/api/webhooks/singapay/transaction';
        $timestamp = (string) time();
        $accessToken = 'simulated-local-token';
        $payload = [
            'status' => 200,
            'success' => true,
            'event' => 'payment-link-transaction',
            'data' => [
                'transaction' => [
                    'reff_no' => (string) $payment->order_id,
                    'type' => 'pl',
                    'status' => (string) $this->option('status'),
                    'amount' => [
                        'value' => number_format((float) $payment->jumlah, 2, '.', ''),
                        'currency' => 'IDR',
                    ],
                    'post_timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        $request = Request::create($endpoint, 'POST', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_TIMESTAMP' => $timestamp,
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
            'HTTP_X_SIGNATURE' => $verifier->computeSignature($endpoint, $accessToken, $payload, $timestamp, $secret),
        ], content: (string) json_encode($payload));

        $result = $processWebhook->execute($request);
        $payment->refresh();

        $this->line("Webhook result: {$result['code']} {$result['message']}");
        $this->line("Payment {$payment->order_id} is now: {$payment->status}");

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
