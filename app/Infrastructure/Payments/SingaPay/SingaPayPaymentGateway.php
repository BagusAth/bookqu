<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\SingaPay;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Domain\Payment\PaymentState;
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
    ) {
    }

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
        $itemName = 'Booking: ' . $service->namalayanan;
        if ($slotCount > 1) {
            $itemName .= ' (' . $slotCount . ' slot)';
        }

        return $this->createPaymentLink($payment, $customerData, $itemName);
    }

    /**
     * Create a SingaPay payment link for a booking payment according to official API specifications.
     *
     * @param Payment $payment
     * @param array<string, mixed> $customerData
     * @param string|null $description
     * @param array<array{name: string, quantity: int, unit_price: int}> $customItems
     * @return array{payment_url: string, external_id: string}
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

        $expiryMinutes = (int) (config('payment.drivers.singapay.expiry_minutes') ?? config('services.singapay.expiry_minutes', 15));

        // SingaPay requires epoch milliseconds for expired_at
        $expiredAt = $payment->expired_at
            ? (int) ($payment->expired_at->getTimestamp() * 1000)
            : (int) (now()->addMinutes($expiryMinutes)->getTimestamp() * 1000);

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

        if (!empty($customItems)) {
            $items = array_map(function ($item) {
                return [
                    'name'       => mb_substr((string) ($item['name'] ?? 'Item'), 0, 100),
                    'quantity'   => max(1, (int) ($item['quantity'] ?? 1)),
                    'unit_price' => (int) ($item['unit_price'] ?? 0),
                ];
            }, $customItems);
        } else {
            $items = [
                [
                    'name'       => $title,
                    'quantity'   => 1,
                    'unit_price' => $totalAmount,
                ],
            ];
        }

        $customerPaysFee = (bool) (config('payment.drivers.singapay.customer_pays_fee') ?? config('services.singapay.customer_pays_fee', false));

        $payload = [
            'reff_no'                    => (string) $payment->order_id,
            'title'                      => $title,
            'max_usage'                  => 1,
            'total_amount'               => $totalAmount,
            'items'                      => $items,
            'required_customer_detail'   => true,
            'customer_pays_fee'          => $customerPaysFee,
            'expired_at'                 => (string) $expiredAt,
            'whitelisted_payment_method' => [],
            'redirect_url'               => $successRedirectUrl,
            'success_redirect_url'       => $successRedirectUrl,
            'expired_redirect_url'       => $expiredRedirectUrl,
            'optional_metadata'          => [
                'payment_id' => (string) $payment->id,
                'order_id'   => (string) $payment->order_id,
                'tenant_id'  => (string) $payment->idtenant,
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
            'provider'    => 'singapay',
            'metode'      => PaymentState::METODE_SINGAPAY,
        ]);

        return [
            'payment_url' => $paymentUrl,
            'external_id' => $externalId,
        ];
    }

    /**
     * Create payment link for owner subscription checkout.
     *
     * @param Payment $payment
     * @param Plan $plan
     * @param array<string, mixed> $payerData
     * @param float $biayaPlatform
     * @return array{payment_url: string, external_id: string}
     * @throws Exception
     */
    public function createSubscriptionPayment(
        Payment $payment,
        Plan $plan,
        array $payerData,
        float $biayaPlatform = 0.0
    ): array {
        $expiryHours = (int) (config('payment.drivers.singapay.subscription_expiry_hours') ?? 1);

        $expiredAt = $payment->expired_at
            ? (int) ($payment->expired_at->getTimestamp() * 1000)
            : (int) (now()->addHours($expiryHours)->getTimestamp() * 1000);

        $totalAmount = (int) round((float) $payment->jumlah);

        $successRedirectUrl = route('owner.checkout.invoice', $payment);
        $expiredRedirectUrl = route('owner.checkout.payment', $payment);

        $title = mb_substr('Subscription Plan ' . ucfirst((string) $plan->namapaket) . ' (1 Bulan)', 0, 100);

        $items = [
            [
                'name'       => $title,
                'quantity'   => 1,
                'unit_price' => (int) $plan->hargabulanan,
            ],
        ];

        if ($biayaPlatform > 0) {
            $items[] = [
                'name'       => 'Biaya Layanan Platform',
                'quantity'   => 1,
                'unit_price' => (int) $biayaPlatform,
            ];
        }

        $customerPaysFee = (bool) (config('payment.drivers.singapay.customer_pays_fee') ?? config('services.singapay.customer_pays_fee', false));

        $payload = [
            'reff_no'                    => (string) $payment->order_id,
            'title'                      => $title,
            'max_usage'                  => 1,
            'total_amount'               => $totalAmount,
            'items'                      => $items,
            'required_customer_detail'   => true,
            'customer_pays_fee'          => $customerPaysFee,
            'expired_at'                 => (string) $expiredAt,
            'whitelisted_payment_method' => [],
            'redirect_url'               => $successRedirectUrl,
            'success_redirect_url'       => $successRedirectUrl,
            'expired_redirect_url'       => $expiredRedirectUrl,
            'optional_metadata'          => [
                'payment_id' => (string) $payment->id,
                'order_id'   => (string) $payment->order_id,
                'tenant_id'  => (string) $payment->idtenant,
                'plan_id'    => (string) $plan->id,
                'type'       => 'subscription',
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
            'provider'    => 'singapay',
            'metode'      => PaymentState::METODE_SINGAPAY,
        ]);

        return [
            'payment_url' => $paymentUrl,
            'external_id' => $externalId,
        ];
    }

    /**
     * Check transaction status with SingaPay.
     *
     * @param Payment $payment
     * @return array<string, mixed>
     */
    public function checkTransactionStatus(Payment $payment): array
    {
        $payment->refresh();

        if ($payment->status === PaymentState::STATUS_SUKSES) {
            return [
                'status'             => PaymentState::STATUS_SUKSES,
                'transaction_status' => 'settlement',
                'message'            => 'Pembayaran berhasil dikonfirmasi.',
                'payment'            => $payment,
            ];
        }

        if ($payment->isExpired() || $payment->status === PaymentState::STATUS_GAGAL) {
            return [
                'status'             => PaymentState::STATUS_GAGAL,
                'is_expired'         => $payment->isExpired(),
                'transaction_status' => 'expire',
                'message'            => 'Waktu pembayaran telah habis atau transaksi dibatalkan.',
                'payment'            => $payment,
            ];
        }

        return [
            'status'             => PaymentState::STATUS_PENDING,
            'transaction_status' => 'pending',
            'message'            => 'Menunggu konfirmasi pembayaran dari SingaPay.',
            'payment'            => $payment,
        ];
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
