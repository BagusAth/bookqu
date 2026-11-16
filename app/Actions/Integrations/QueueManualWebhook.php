<?php

declare(strict_types=1);

namespace App\Actions\Integrations;

use App\Infrastructure\Integrations\WebhookReplay;
use App\Jobs\DeliverOwnerWebhook;
use App\Models\Tenant;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class QueueManualWebhook
{
    public function __construct(private WebhookReplay $replay) {}

    public function test(Tenant $tenant, int $endpointId): void
    {
        $endpoint = WebhookEndpoint::where('idtenant', $tenant->id)->findOrFail($endpointId);
        $delivery = WebhookDelivery::create([
            'idtenant' => $tenant->id,
            'endpoint_id' => $endpoint->id,
            'event_id' => (string) Str::uuid(),
            'event_name' => WebhookReplay::TEST_EVENT,
        ]);

        $this->dispatch($delivery, null);
    }

    public function redeliver(Tenant $tenant, int $deliveryId): void
    {
        [$delivery, $subjectId] = DB::transaction(function () use ($tenant, $deliveryId): array {
            $delivery = WebhookDelivery::where('idtenant', $tenant->id)->lockForUpdate()->findOrFail($deliveryId);
            $subjectId = $delivery->event_name === WebhookReplay::TEST_EVENT ? null : $this->replay->subjectId($delivery);
            if ($delivery->endpoint_id === null || ! in_array($delivery->status, ['delivered', 'failed', 'skipped'], true)
                || ($delivery->event_name !== WebhookReplay::TEST_EVENT && $subjectId === null)) {
                throw ValidationException::withMessages(['delivery' => 'Pengiriman ini belum bisa dikirim ulang.']);
            }
            WebhookEndpoint::where('idtenant', $tenant->id)->findOrFail($delivery->endpoint_id);
            $delivery->update(['status' => 'pending', 'attempts' => 0, 'http_status' => null, 'elapsed_ms' => null]);

            return [$delivery, $subjectId];
        });

        $this->dispatch($delivery, $subjectId);
    }

    private function dispatch(WebhookDelivery $delivery, ?int $subjectId): void
    {
        try {
            Bus::dispatch((new DeliverOwnerWebhook((int) $delivery->idtenant, (int) $delivery->id, $subjectId))->afterCommit());
        } catch (Throwable $exception) {
            WebhookDelivery::where('idtenant', $delivery->idtenant)->whereKey($delivery->id)
                ->where('status', 'pending')->update(['status' => 'failed']);
            Log::warning('Manual webhook could not be queued.', ['delivery_id' => $delivery->id, 'exception_type' => $exception::class]);

            throw ValidationException::withMessages(['delivery' => 'Pengiriman belum dapat dijadwalkan. Coba lagi.']);
        }
    }
}
