<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Integrations\BookingIntegrationEvent;
use App\Domain\Integrations\RefundIntegrationEvent;
use App\Domain\Modules\ModuleRelease;
use App\Domain\Subscription\EntitlementRules;
use App\Jobs\DeliverOwnerWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class QueueOwnerWebhook
{
    public function handle(BookingIntegrationEvent|RefundIntegrationEvent $event): void
    {
        if (ModuleRelease::state('api') !== 'released') {
            return;
        }

        $context = app(TenantContext::class);
        $previous = $context->getTenantId();
        $context->setTenantId($event->tenantId);
        try {
            $tenant = $context->tenant();
            if ($tenant === null || ! EntitlementRules::canAccessFeature($tenant->subscription()->with('plan')->first(), 'webhooks')['allowed']) {
                return;
            }

            $eventId = (string) Str::uuid();
            $subjectId = $event instanceof BookingIntegrationEvent ? $event->bookingId : $event->refundId;
            foreach (WebhookEndpoint::where('idtenant', $event->tenantId)->pluck('id') as $endpointId) {
                $delivery = WebhookDelivery::create([
                    'idtenant' => $event->tenantId, 'endpoint_id' => $endpointId, 'event_id' => $eventId, 'event_name' => $event->name,
                ]);
                DeliverOwnerWebhook::dispatch($event->tenantId, (int) $delivery->id, $subjectId);
            }
        } catch (Throwable) {
            Log::warning('Webhook queueing failed.', ['tenant_id' => $event->tenantId, 'event' => $event->name]);
        } finally {
            $context->setTenantId($previous);
        }
    }
}
