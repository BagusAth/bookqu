<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\SingaPay;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SingaPayWebhookVerifier
{
    /**
     * Verify incoming SingaPay webhook request HMAC signature according to official specification.
     *
     * StringToSign = METHOD:ENDPOINT:ACCESS_TOKEN:HASHED_BODY:TIMESTAMP
     *
     * @param Request $request
     * @return bool
     */
    public function verify(Request $request): bool
    {
        $receivedSignature = (string) $request->header('X-Signature', '');
        $timestamp         = (string) $request->header('X-Timestamp', '');
        $authHeader        = (string) $request->header('Authorization', '');
        $accessToken       = trim((string) preg_replace('/^Bearer\s+/i', '', $authHeader));

        if (empty($receivedSignature)) {
            Log::warning('SingaPay Webhook: Missing X-Signature header');
            return false;
        }

        $rawBody = (string) $request->getContent();
        $bodyArray = json_decode($rawBody, true);

        if (!is_array($bodyArray) || json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('SingaPay Webhook: Invalid JSON body');
            return false;
        }

        // 1. Sort object keys recursively and alphabetically
        $this->sortRecursive($bodyArray);

        // 2. Normalize and hash with SHA-256
        $normalizedJson = json_encode($bodyArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hashedBody     = hash('sha256', (string) $normalizedJson);

        // 3. Extract method and exact endpoint URI
        $method   = strtoupper($request->method());
        $endpoint = $request->getRequestUri();

        // 4. Construct string to sign: METHOD:ENDPOINT:ACCESS_TOKEN:HASHED_BODY:TIMESTAMP
        $stringToSign = "{$method}:{$endpoint}:{$accessToken}:{$hashedBody}:{$timestamp}";

        // 5. Official signing key is Client Secret per SingaPay documentation
        $clientSecret = (string) config('services.singapay.client_secret');

        if (empty($clientSecret)) {
            Log::error('SingaPay Webhook: No client secret configured in services.singapay');
            return false;
        }

        $calculatedSignature = hash_hmac('sha512', $stringToSign, $clientSecret);

        if (hash_equals($calculatedSignature, $receivedSignature)) {
            return true;
        }

        Log::warning('SingaPay Webhook: HMAC signature mismatch', [
            'endpoint'  => $endpoint,
            'timestamp' => $timestamp,
        ]);

        return false;
    }

    /**
     * Verify timestamp against replay attacks within configurable tolerance.
     *
     * @param Request $request
     * @return bool
     */
    public function verifyTimestamp(Request $request): bool
    {
        $timestamp = (string) $request->header('X-Timestamp', '');
        return $this->isValidTimestamp($timestamp);
    }

    /**
     * Validate timestamp string (epoch seconds, epoch ms, or ISO-8601).
     *
     * @param string $timestamp
     * @param int|null $toleranceSeconds
     * @return bool
     */
    public function isValidTimestamp(string $timestamp, ?int $toleranceSeconds = null): bool
    {
        if (trim($timestamp) === '') {
            return false;
        }

        $tolerance = $toleranceSeconds ?? (int) config('services.singapay.webhook_tolerance_seconds', 300);
        $now = time();
        $timestampSeconds = null;

        if (is_numeric($timestamp)) {
            $num = (int) $timestamp;
            // Detect epoch in milliseconds (e.g. > 10^11)
            if ($num > 9999999999) {
                $num = (int) round($num / 1000);
            }
            $timestampSeconds = $num;
        } else {
            try {
                $parsed = Carbon::parse($timestamp);
                $timestampSeconds = $parsed->getTimestamp();
            } catch (\Throwable) {
                return false;
            }
        }

        if ($timestampSeconds === null) {
            return false;
        }

        return abs($now - $timestampSeconds) <= $tolerance;
    }

    /**
     * Recursively sort associative arrays by key in ascending alphabetical order.
     *
     * @param array<mixed> &$array
     */
    public function sortRecursive(array &$array): void
    {
        ksort($array, SORT_STRING);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->sortRecursive($value);
            }
        }
    }

    /**
     * Helper to compute signature for testing purposes.
     *
     * @param string $endpoint
     * @param string $accessToken
     * @param array<string, mixed> $body
     * @param string $timestamp
     * @param string $key
     * @param string $method
     * @return string
     */
    public function computeSignature(
        string $endpoint,
        string $accessToken,
        array $body,
        string $timestamp,
        string $key,
        string $method = 'POST'
    ): string {
        $sorted = $body;
        $this->sortRecursive($sorted);
        $normalized = json_encode($sorted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hashedBody = hash('sha256', (string) $normalized);

        $stringToSign = "{$method}:{$endpoint}:{$accessToken}:{$hashedBody}:{$timestamp}";

        return hash_hmac('sha512', $stringToSign, $key);
    }
}
