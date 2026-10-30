<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\SingaPay;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Domain\Payment\PaymentRules;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\ProviderOutcome;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Service;
use App\Support\CustomerBookingRoutes;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SingaPayPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected SingaPayClient $client,
        protected SingaPayWebhookVerifier $verifier
    ) {}

    /**
     * Get the identifier name of the payment gateway driver.
     */
    public function getDriverName(): string
    {
        return 'singapay';
    }

    /**
     * Create payment link for customer booking.
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

        return $this->createPaymentLink($payment, $customerData, $itemName);
    }

    /**
     * Create a SingaPay payment link for a booking payment according to official API specifications.
     *
     * @param  array<string, mixed>  $customerData
     * @param  array<array{name: string, quantity: int, unit_price: int}>  $customItems
     * @return array{payment_url: string, external_id: string}
     *
     * @throws Exception
     */
    public function createPaymentLink(
        Payment $payment,
        array $customerData,
        ?string $description = null,
        array $customItems = []
    ): array {
        $tenant = $payment->tenant;
        $tenantSlug = $tenant?->slug ?? 'tenant';

        // SingaPay requires epoch milliseconds; the link expires at the payment's own deadline (ADR-0009)
        $expiredAt = (int) (($payment->expired_at ?? PaymentRules::deadlineFor(PaymentState::TIPE_BOOKING))->getTimestamp() * 1000);

        $totalAmount = (int) round((float) $payment->jumlah);

        $successRedirectUrl = CustomerBookingRoutes::url('customer.booking.invoice', [
            $tenantSlug,
            $payment->order_id ?? $payment->id,
        ]);

        $expiredRedirectUrl = CustomerBookingRoutes::url('customer.booking.payment', [
            $tenantSlug,
            $payment->order_id ?? $payment->id,
        ]);

        $title = mb_substr((string) ($description ?: ("Booking #{$payment->order_id}")), 0, 100);

        if (! empty($customItems)) {
            $items = array_map(function ($item) {
                return [
                    'name' => mb_substr((string) ($item['name'] ?? 'Item'), 0, 100),
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    'unit_price' => (int) ($item['unit_price'] ?? 0),
                ];
            }, $customItems);
        } else {
            $items = [
                [
                    'name' => $title,
                    'quantity' => 1,
                    'unit_price' => $totalAmount,
                ],
            ];
        }

        $customerPaysFee = (bool) (config('payment.drivers.singapay.customer_pays_fee') ?? config('services.singapay.customer_pays_fee', false));

        $payload = [
            'reff_no' => (string) $payment->order_id,
            'title' => $title,
            'max_usage' => 1,
            'total_amount' => $totalAmount,
            'items' => $items,
            'required_customer_detail' => true,
            'customer_pays_fee' => $customerPaysFee,
            'expired_at' => (string) $expiredAt,
            'whitelisted_payment_method' => [],
            'redirect_url' => $successRedirectUrl,
            'success_redirect_url' => $successRedirectUrl,
            'expired_redirect_url' => $expiredRedirectUrl,
            'optional_metadata' => [
                'payment_id' => (string) $payment->id,
                'order_id' => (string) $payment->order_id,
                'tenant_id' => (string) $payment->idtenant,
            ],
        ];

        $response = $this->client->createPaymentLink($payload);

        $paymentUrl = $response['data']['payment_url']
            ?? $response['data']['url']
            ?? $response['data']['payment_link']['payment_url']
            ?? '';

        $externalId = (string) ($response['data']['id']
            ?? $response['data']['payment_link']['id']
            ?? $payment->order_id);

        if (empty($paymentUrl)) {
            Log::error('SingaPay Create Payment Link: Missing payment_url in response', [
                'order_id' => $payment->order_id,
                'response' => $response,
            ]);

            throw new Exception('Payment gateway did not return a valid payment link URL.');
        }

        // Save URL & external ID without changing the payment's pending status
        $payment->update([
            'payment_url' => $paymentUrl,
            'external_id' => $externalId,
            'provider' => 'singapay',
            'metode' => PaymentState::METODE_SINGAPAY,
        ]);

        return [
            'payment_url' => $paymentUrl,
            'external_id' => $externalId,
        ];
    }

    /**
     * Create payment link for owner subscription checkout.
     *
     * @param  array<string, mixed>  $payerData
     * @return array{payment_url: string, external_id: string}
     *
     * @throws Exception
     */
    public function createSubscriptionPayment(
        Payment $payment,
        Plan $plan,
        array $payerData,
        float $biayaPlatform = 0.0
    ): array {
        $expiredAt = (int) (($payment->expired_at ?? PaymentRules::deadlineFor(PaymentState::TIPE_SUBSCRIPTION))->getTimestamp() * 1000);

        $totalAmount = (int) round((float) $payment->jumlah);

        $successRedirectUrl = route('owner.checkout.invoice', $payment);
        $expiredRedirectUrl = route('owner.checkout.payment', $payment);

        $title = mb_substr('Subscription Plan '.ucfirst((string) $plan->namapaket).' (1 Bulan)', 0, 100);

        $items = [
            [
                'name' => $title,
                'quantity' => 1,
                'unit_price' => (int) $plan->hargabulanan,
            ],
        ];

        if ($biayaPlatform > 0) {
            $items[] = [
                'name' => 'Biaya Layanan Platform',
                'quantity' => 1,
                'unit_price' => (int) $biayaPlatform,
            ];
        }

        $customerPaysFee = (bool) (config('payment.drivers.singapay.customer_pays_fee') ?? config('services.singapay.customer_pays_fee', false));

        $payload = [
            'reff_no' => (string) $payment->order_id,
            'title' => $title,
            'max_usage' => 1,
            'total_amount' => $totalAmount,
            'items' => $items,
            'required_customer_detail' => true,
            'customer_pays_fee' => $customerPaysFee,
            'expired_at' => (string) $expiredAt,
            'whitelisted_payment_method' => [],
            'redirect_url' => $successRedirectUrl,
            'success_redirect_url' => $successRedirectUrl,
            'expired_redirect_url' => $expiredRedirectUrl,
            'optional_metadata' => [
                'payment_id' => (string) $payment->id,
                'order_id' => (string) $payment->order_id,
                'tenant_id' => (string) $payment->idtenant,
                'plan_id' => (string) $plan->id,
                'type' => 'subscription',
            ],
        ];

        $response = $this->client->createPaymentLink($payload);

        $paymentUrl = $response['data']['payment_url']
            ?? $response['data']['url']
            ?? $response['data']['payment_link']['payment_url']
            ?? '';

        $externalId = (string) ($response['data']['id']
            ?? $response['data']['payment_link']['id']
            ?? $payment->order_id);

        if (empty($paymentUrl)) {
            Log::error('SingaPay Create Subscription Payment Link: Missing payment_url in response', [
                'order_id' => $payment->order_id,
                'response' => $response,
            ]);

            throw new Exception('Payment gateway did not return a valid payment link URL.');
        }

        $payment->update([
            'payment_url' => $paymentUrl,
            'external_id' => $externalId,
            'provider' => 'singapay',
            'metode' => PaymentState::METODE_SINGAPAY,
        ]);

        return [
            'payment_url' => $paymentUrl,
            'external_id' => $externalId,
        ];
    }

    /**
     * SingaPay's client offers no transaction status endpoint, so BookQu cannot ask
     * the provider; the payment relies on webhooks and the late-success path
     * (ADR-0007 decision 4). The local database is never used as an answer.
     */
    public function queryOutcome(Payment $payment): ProviderOutcome
    {
        return ProviderOutcome::unknown($this->getDriverName(), (string) $payment->order_id);
    }

    /**
     * SingaPay payment links cannot be cancelled through the client yet; the link
     * expires on its own and a later success becomes a late success.
     */
    public function cancel(Payment $payment): void {}

    /**
     * Translate a verified SingaPay Money In / Payment Link notification into
     * BookQu's vocabulary. The caller must have verified timestamp and signature.
     *
     * @param  array<string, mixed>  $payload
     */
    public function outcomeFromNotification(array $payload): ProviderOutcome
    {
        $scalar = static fn (mixed $value): ?string => is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;

        // The root-level "status" can be an envelope status (e.g. HTTP 200); only the
        // transaction status counts, and a missing one is never success.
        $rawStatus = $scalar($payload['data']['transaction']['status'] ?? $payload['data']['status'] ?? null);

        $amount = $payload['data']['transaction']['amount']['value']
            ?? $payload['data']['amount']['value']
            ?? $payload['data']['payment']['additional_info']['payment_link']['total_amount']
            ?? $payload['data']['total_amount']
            ?? null;

        return new ProviderOutcome(
            provider: $this->getDriverName(),
            // data.transaction.reff_no is the primary identifier, then payment-link structures.
            references: array_values(array_filter([
                $scalar($payload['data']['transaction']['reff_no'] ?? null),
                $scalar($payload['data']['payment']['additional_info']['payment_link']['reff_no'] ?? null),
                $scalar($payload['data']['reff_no'] ?? null),
                $scalar($payload['data']['payment_link']['reff_no'] ?? null),
                $scalar($payload['order_id'] ?? null),
                $scalar($payload['data']['payment']['additional_info']['payment_link']['id'] ?? null),
                $scalar($payload['data']['id'] ?? null),
            ])),
            result: PaymentState::mapSingaPayStatus($rawStatus),
            amount: is_numeric($amount) ? (string) $amount : null,
            currency: $scalar($payload['data']['transaction']['amount']['currency'] ?? $payload['data']['amount']['currency'] ?? null),
            transactionId: $scalar($payload['data']['transaction']['id'] ?? null),
            method: PaymentState::METODE_SINGAPAY,
            eventTime: $scalar($payload['data']['transaction']['created_at'] ?? $payload['timestamp'] ?? null),
        );
    }

    /**
     * Verify incoming webhook notification HMAC signature.
     */
    public function verifyWebhook(Request $request): bool
    {
        return $this->verifier->verify($request);
    }

    /**
     * Verify incoming webhook notification timestamp (replay protection).
     */
    public function verifyTimestamp(Request $request): bool
    {
        return $this->verifier->verifyTimestamp($request);
    }

    /**
     * Get underlying verifier instance.
     */
    public function getVerifier(): SingaPayWebhookVerifier
    {
        return $this->verifier;
    }

    /**
     * Get underlying HTTP client instance.
     */
    public function getClient(): SingaPayClient
    {
        return $this->client;
    }
}
