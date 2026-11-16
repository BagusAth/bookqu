<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Modules\ModuleRelease;
use App\Domain\Subscription\EntitlementRules;
use App\Infrastructure\Integrations\SignedWebhook;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Notifications\OwnerWebhookFailed;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

final class DeliverOwnerWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 20;

    public function __construct(public int $tenantId, public int $deliveryId, public int $subjectId) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(TenantContext $context, SignedWebhook $sender): void
    {
        $previous = $context->getTenantId();
        $context->setTenantId($this->tenantId);
        try {
            Cache::lock('owner-webhook:'.$this->deliveryId, 25)->block(5, function () use ($context, $sender): void {
                $delivery = WebhookDelivery::where('idtenant', $this->tenantId)->find($this->deliveryId);
                if ($delivery === null || $delivery->status !== 'pending') {
                    return;
                }
                $tenant = $context->tenant();
                $endpoint = WebhookEndpoint::where('idtenant', $this->tenantId)->find($delivery->endpoint_id);
                if ($endpoint === null || $tenant === null || ModuleRelease::state('api') !== 'released'
                    || ! EntitlementRules::canAccessFeature($tenant->subscription()->with('plan')->first(), 'webhooks')['allowed']) {
                    $delivery->update(['status' => 'skipped']);

                    return;
                }
                if ((int) $delivery->attempts >= $this->tries) {
                    $this->markFailed($tenant);

                    return;
                }

                $started = hrtime(true);
                $status = $sender->deliver($endpoint, $delivery, $this->subjectId);
                $success = $status !== null && $status >= 200 && $status < 300;
                $delivery->update([
                    'attempts' => (int) $delivery->attempts + 1,
                    'http_status' => $status,
                    'elapsed_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                    'status' => $success ? 'delivered' : 'pending',
                ]);

                if (! $success) {
                    throw new RuntimeException('Webhook delivery failed.');
                }
            });
        } finally {
            $context->setTenantId($previous);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $context = app(TenantContext::class);
        $previous = $context->getTenantId();
        $context->setTenantId($this->tenantId);
        try {
            $tenant = $context->tenant();
            if ($tenant !== null) {
                $this->markFailed($tenant);
            }
        } finally {
            $context->setTenantId($previous);
        }
    }

    private function markFailed(Tenant $tenant): void
    {
        $updated = WebhookDelivery::where('idtenant', $tenant->id)->whereKey($this->deliveryId)
            ->where('status', 'pending')->update(['status' => 'failed']);
        $owner = $tenant->user;
        if ($updated === 1 && $owner instanceof User) {
            $owner->notify(new OwnerWebhookFailed);
        }
    }
}
