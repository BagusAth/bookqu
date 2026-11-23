<?php

declare(strict_types=1);

namespace App\Infrastructure\Integrations;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;
use Throwable;

final class SignedWebhook
{
    public function __construct(private PublicWebhookTarget $targets) {}

    public function deliver(WebhookEndpoint $endpoint, WebhookDelivery $delivery, ?int $subjectId): ?int
    {
        $target = $this->targets->resolve($endpoint->url);
        if ($target === null || ! extension_loaded('curl')) {
            return null;
        }

        $timestamp = (string) now()->timestamp;
        $body = json_encode([
            'id' => $delivery->event_id,
            'event' => $delivery->event_name,
            'subject_id' => $subjectId,
        ], JSON_THROW_ON_ERROR);
        $address = str_contains($target['address'], ':') ? '['.$target['address'].']' : $target['address'];
        $resolve = $target['literal'] ? [] : [$target['host'].':'.$target['port'].':'.$address];

        try {
            return Http::withoutRedirecting()->connectTimeout(3)->timeout(8)
                ->setHandler(new CurlHandler)
                ->withOptions([
                    'proxy' => '',
                    'sink' => FnStream::decorate(Utils::streamFor(''), [
                        'write' => static fn (string $bytes): int => strlen($bytes),
                    ]),
                    'curl' => [
                        CURLOPT_RESOLVE => $resolve,
                        CURLOPT_FRESH_CONNECT => true,
                    ],
                ])
                ->withHeaders([
                    'X-BookQu-Event-Id' => $delivery->event_id,
                    'X-BookQu-Timestamp' => $timestamp,
                    'X-BookQu-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $endpoint->secret),
                ])->withBody($body, 'application/json')->post($endpoint->url)->status();
        } catch (Throwable) {
            return null;
        }
    }
}
