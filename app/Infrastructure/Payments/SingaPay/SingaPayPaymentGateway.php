<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\SingaPay;

use App\Models\Payment;
use App\Support\CustomerBookingRoutes;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SingaPayPaymentGateway
{
    public function __construct(
        protected SingaPayClient $client,
        protected SingaPayWebhookVerifier $verifier
    ) {
    }

    /**
     * Create a SingaPay payment link for a booking payment.
     *
     * @param Payment $payment
     * @param array<string, mixed> $customerData
     * @param string|null $description
     * @return array{payment_url: string, external_id: string}
     * @throws Exception
     */
    public function createPaymentLink(Payment $payment, array $customerData, ?string $description = null): array
    {
        $tenant = $payment->tenant;
        $tenantSlug = $tenant?->slug ?? 'tenant';

        $expiryMinutes = (int) config('services.singapay.expiry_minutes', 15);
        $expiredAt = $payment->expired_at
            ? $payment->expired_at->toIso8601String()
            : now()->addMinutes($expiryMinutes)->toIso8601String();

        $successRedirectUrl = CustomerBookingRoutes::url('customer.booking.invoice', [
            $tenantSlug,
            $payment->order_id ?? $payment->id,
        ]);

        $expiredRedirectUrl = CustomerBookingRoutes::url('customer.booking.payment', [
            $tenantSlug,
            $payment->order_id ?? $payment->id,
        ]);

        $payload = [
            'reff_no'              => (string) $payment->order_id,
            'payment_link_type'    => 'total',
            'total_amount'         => (int) round((float) $payment->jumlah),
            'description'          => $description ?: ("Booking #{$payment->order_id}"),
            'max_usage'            => 1,
            'expired_at'           => $expiredAt,
            'customer_name'        => (string) ($customerData['namapelanggan'] ?? $payment->nama_pembayar ?? 'Customer'),
            'customer_email'       => (string) ($customerData['email'] ?? $payment->email_pembayar ?? 'customer@example.com'),
            'customer_phone'       => (string) ($customerData['nomorhp'] ?? $payment->hp_pembayar ?? '08123456789'),
            'success_redirect_url' => $successRedirectUrl,
            'expired_redirect_url' => $expiredRedirectUrl,
        ];

        $response = $this->client->createPaymentLink($payload);

        $paymentUrl = $response['data']['payment_url']
            ?? $response['data']['payment_link']['payment_url']
            ?? $response['data']['url']
            ?? '';

        $externalId = (string) ($response['data']['id']
            ?? $response['data']['payment_link']['id']
            ?? $payment->order_id);

        if (empty($paymentUrl)) {
            Log::error('SingaPay Create Payment Link: Missing payment_url in response', [
                'response' => $response,
                'order_id' => $payment->order_id,
            ]);

            throw new Exception('Payment gateway did not return a valid payment link URL.');
        }

        $payment->update([
            'payment_url' => $paymentUrl,
            'external_id' => $externalId,
            'provider'    => 'singapay',
        ]);

        return [
            'payment_url' => $paymentUrl,
            'external_id' => $externalId,
        ];
    }

    /**
     * Verify incoming webhook notification signature.
     */
    public function verifyWebhook(Request $request): bool
    {
        return $this->verifier->verify($request);
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
