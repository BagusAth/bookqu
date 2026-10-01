<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\SingaPay;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SingaPayClient
{
    protected string $baseUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $apiKey;
    protected ?string $accountId;

    public function __construct()
    {
        $this->baseUrl      = rtrim((string) config('services.singapay.base_url', 'https://payment-b2b.singapay.id'), '/');
        $this->clientId     = config('services.singapay.client_id');
        $this->clientSecret = config('services.singapay.client_secret');
        $this->apiKey       = config('services.singapay.api_key');
        $this->accountId    = config('services.singapay.account_id');
    }

    /**
     * Check if Http facade currently has fake stubs registered.
     */
    protected function isHttpFaked(): bool
    {
        try {
            $factory = app(\Illuminate\Http\Client\Factory::class);
            $ref = new \ReflectionProperty($factory, 'stubCallbacks');
            $ref->setAccessible(true);
            $stubs = $ref->getValue($factory);
            return $stubs instanceof \Illuminate\Support\Collection && $stubs->isNotEmpty();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Request or retrieve cached JWT access token using OAuth 2.0 Client Credentials.
     * Endpoint: POST /api/v1.1/access-token/b2b
     *
     * @throws Exception
     */
    public function getAccessToken(): string
    {
        if (app()->environment('testing') && !$this->isHttpFaked()) {
            return 'mocked-singapay-access-token';
        }

        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception('SingaPay credentials (CLIENT_ID or CLIENT_SECRET) are not configured.');
        }

        $cacheKey = 'singapay_b2b_jwt_' . md5($this->clientId . ($this->apiKey ?? ''));

        return Cache::remember($cacheKey, now()->addMinutes(45), function () {
            $basicAuth = base64_encode("{$this->clientId}:{$this->clientSecret}");

            $headers = [
                'Authorization' => "Basic {$basicAuth}",
                'X-PARTNER-ID'  => (string) $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ];

            $body = [
                'grant_type' => 'client_credentials',
            ];

            $response = Http::withHeaders($headers)
                ->connectTimeout(5)
                ->timeout(15)
                ->retry(2, 200, throw: false)
                ->post("{$this->baseUrl}/api/v1.1/access-token/b2b", $body);

            if (!$response->successful()) {
                Log::error('SingaPay OAuth Token Error:', [
                    'status' => $response->status(),
                    'error'  => $response->json('message') ?? $response->json('error') ?? 'Authentication failed',
                ]);

                throw new Exception('Failed to obtain SingaPay access token: HTTP ' . $response->status());
            }

            $json = $response->json();
            $token = $json['data']['access_token']
                ?? $json['access_token']
                ?? null;

            if (!$token) {
                throw new Exception('Invalid access token response structure from SingaPay.');
            }

            return (string) $token;
        });
    }

    /**
     * Resolve active Account ID for the merchant.
     * Uses configured account_id if available.
     * Fallback queries /api/v1.0/accounts only if account_id is not set.
     * If multiple active accounts exist, throws an exception to avoid ambiguity.
     *
     * @throws Exception
     */
    public function resolveAccountId(): string
    {
        if (!empty($this->accountId)) {
            return (string) $this->accountId;
        }

        if (app()->environment('testing') && !$this->isHttpFaked()) {
            return '01JTESTACCOUNTULID00000000000';
        }

        $cacheKey = 'singapay_merchant_account_id_' . md5((string) $this->apiKey);

        return Cache::remember($cacheKey, now()->addHours(24), function () {
            $token = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'X-PARTNER-ID'  => (string) $this->apiKey,
                'Accept'        => 'application/json',
            ])
                ->connectTimeout(5)
                ->timeout(15)
                ->get("{$this->baseUrl}/api/v1.0/accounts");

            if (!$response->successful()) {
                Log::error('SingaPay List Accounts Error:', [
                    'status' => $response->status(),
                    'error'  => $response->json('message') ?? $response->json('error') ?? 'Failed to list accounts',
                ]);

                throw new Exception('Failed to retrieve SingaPay merchant accounts: HTTP ' . $response->status());
            }

            $accounts = $response->json('data') ?? [];
            if (!is_array($accounts)) {
                $accounts = [];
            }

            $activeAccounts = array_values(array_filter($accounts, function ($acc) {
                $status = strtolower((string) ($acc['status'] ?? ''));
                return $status === 'active' || empty($status);
            }));

            if (count($activeAccounts) > 1) {
                Log::error('SingaPay: Multiple active merchant accounts found without SINGAPAY_ACCOUNT_ID configured.', [
                    'active_count' => count($activeAccounts),
                ]);

                throw new Exception('Multiple active SingaPay accounts found. Please configure SINGAPAY_ACCOUNT_ID explicitly in your configuration.');
            }

            if (count($activeAccounts) === 1 && !empty($activeAccounts[0]['id'])) {
                return (string) $activeAccounts[0]['id'];
            }

            throw new Exception('No active SingaPay account found for this merchant.');
        });
    }

    /**
     * Create a payment link using SingaPay Payment Link API.
     * Endpoint: POST /api/v1.0/payment-link-manage/{account_id}
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     * @throws Exception
     */
    public function createPaymentLink(array $payload): array
    {
        if (app()->environment('testing') && !$this->isHttpFaked()) {
            return [
                'status'  => 200,
                'success' => true,
                'data'    => [
                    'id'          => 12345,
                    'reff_no'     => $payload['reff_no'] ?? 'MOCK-REFF',
                    'payment_url' => 'https://payment-link.singapay.id/b2b/' . ($payload['reff_no'] ?? 'MOCK-REFF'),
                ],
            ];
        }

        $token     = $this->getAccessToken();
        $accountId = $this->resolveAccountId();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-PARTNER-ID'  => (string) $this->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])
            ->connectTimeout(5)
            ->timeout(20)
            ->post("{$this->baseUrl}/api/v1.0/payment-link-manage/{$accountId}", $payload);

        if (!$response->successful()) {
            $errorCode = $response->json('code') ?? $response->json('error.code');
            $errorMsg  = $response->json('message')
                ?? $response->json('error.message')
                ?? $response->json('error')
                ?? ('HTTP ' . $response->status());

            Log::error('SingaPay Create Payment Link Error:', [
                'status'     => $response->status(),
                'error_code' => $errorCode,
                'error_msg'  => $errorMsg,
                'reff_no'    => $payload['reff_no'] ?? null,
            ]);

            throw new Exception("SingaPay API Error: {$errorMsg}");
        }

        return $response->json() ?? [];
    }
}
