<?php

declare(strict_types=1);

namespace App\Infrastructure\Integrations;

use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class WebhookReplay
{
    public const TEST_EVENT = 'webhook.test';

    public function remember(WebhookDelivery $delivery, int $subjectId): void
    {
        try {
            Cache::put($this->key($delivery), $subjectId, now()->addDays(7));
        } catch (Throwable) {
            Log::warning('Webhook replay metadata could not be retained.', ['delivery_id' => $delivery->id]);
        }
    }

    public function subjectId(WebhookDelivery $delivery): ?int
    {
        $subjectId = Cache::get($this->key($delivery));

        return is_int($subjectId) ? $subjectId : null;
    }

    public function canReplay(WebhookDelivery $delivery): bool
    {
        return $delivery->endpoint_id !== null
            && in_array($delivery->status, ['delivered', 'failed', 'skipped'], true)
            && ($delivery->event_name === self::TEST_EVENT || $this->subjectId($delivery) !== null);
    }

    private function key(WebhookDelivery $delivery): string
    {
        return 'owner-webhook-replay:'.$delivery->idtenant.':'.$delivery->id.':'.$delivery->event_id;
    }
}
