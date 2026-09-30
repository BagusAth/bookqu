<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\SingaPay;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SingaPayWebhookVerifier
{
    /**
     * Verify incoming SingaPay webhook request HMAC signature according to official specification.
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

        // 3. Extract exact endpoint (path and query string)
        $endpoint = $request->getRequestUri();

        // 4. Construct string to sign: POST:ENDPOINT:ACCESS_TOKEN:HASHED_BODY:TIMESTAMP
        $stringToSign = "POST:{$endpoint}:{$accessToken}:{$hashedBody}:{$timestamp}";

        // 5. Retrieve secret keys (Primary: HMAC_VALIDATION_KEY, Fallback: CLIENT_SECRET)
        $hmacKey      = (string) config('services.singapay.hmac_validation_key');
        $clientSecret = (string) config('services.singapay.client_secret');

        $primaryKey = !empty($hmacKey) ? $hmacKey : $clientSecret;

        if (empty($primaryKey)) {
            Log::error('SingaPay Webhook: No validation key configured in services.singapay');
            return false;
        }

        $calculatedSignature = hash_hmac('sha512', $stringToSign, $primaryKey);

        if (hash_equals($calculatedSignature, $receivedSignature)) {
            return true;
        }

        // Secondary check with client_secret if primaryKey was hmac_validation_key and differed
        if (!empty($clientSecret) && $clientSecret !== $primaryKey) {
            $altSignature = hash_hmac('sha512', $stringToSign, $clientSecret);
            if (hash_equals($altSignature, $receivedSignature)) {
                return true;
            }
        }

        Log::warning('SingaPay Webhook: HMAC signature mismatch', [
            'endpoint'  => $endpoint,
            'timestamp' => $timestamp,
        ]);

        return false;
    }

    /**
     * Recursively sort associative arrays by key in ascending alphabetical order.
     *
     * @param array<mixed> &$array
     */
    protected function sortRecursive(array &$array): void
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
     * @return string
     */
    public function computeSignature(string $endpoint, string $accessToken, array $body, string $timestamp, string $key): string
    {
        $sorted = $body;
        $this->sortRecursive($sorted);
        $normalized = json_encode($sorted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hashedBody = hash('sha256', (string) $normalized);

        $stringToSign = "POST:{$endpoint}:{$accessToken}:{$hashedBody}:{$timestamp}";

        return hash_hmac('sha512', $stringToSign, $key);
    }
}
