<?php

declare(strict_types=1);

namespace App\Actions\Integrations;

use App\Infrastructure\Integrations\PublicWebhookTarget;
use App\Models\OwnerApiKey;
use App\Models\Tenant;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Validation\ValidationException;

final class ManageOwnerApiCredentials
{
    public function __construct(private PublicWebhookTarget $targets) {}

    /** @return array<string, mixed> */
    public function overview(Tenant $tenant): array
    {
        return [
            'keys' => OwnerApiKey::where('idtenant', $tenant->id)->whereNull('revoked_at')->latest('id')
                ->get(['id', 'name', 'prefix']),
            'endpoints' => WebhookEndpoint::where('idtenant', $tenant->id)->latest('id')->get(['id', 'name', 'url']),
            'deliveries' => WebhookDelivery::where('idtenant', $tenant->id)->latest('id')->limit(20)
                ->get(['id', 'event_id', 'event_name', 'attempts', 'http_status', 'elapsed_ms', 'status', 'created_at'])
                ->each(function (WebhookDelivery $delivery): void {
                    $delivery->setAttribute('status_label', match ($delivery->status) {
                        'pending' => 'Menunggu pengiriman',
                        'delivered' => 'Terkirim',
                        'failed' => 'Gagal dikirim',
                        default => 'Status tidak tersedia',
                    });
                }),
        ];
    }

    public function createKey(Tenant $tenant, string $name): string
    {
        $key = 'bq_'.bin2hex(random_bytes(32));
        OwnerApiKey::create([
            'idtenant' => $tenant->id, 'name' => $name, 'prefix' => substr($key, 0, 12), 'digest' => hash('sha256', $key),
        ]);

        return $key;
    }

    public function revoke(Tenant $tenant, int $id): void
    {
        OwnerApiKey::where('idtenant', $tenant->id)->findOrFail($id)->update(['revoked_at' => now()]);
    }

    public function createWebhook(Tenant $tenant, string $name, string $url): string
    {
        if ($this->targets->resolve($url) === null) {
            throw ValidationException::withMessages(['url' => 'Gunakan alamat HTTPS yang mengarah ke host publik.']);
        }

        $secret = bin2hex(random_bytes(32));
        WebhookEndpoint::create(['idtenant' => $tenant->id, 'name' => $name, 'url' => $url, 'secret' => $secret]);

        return $secret;
    }

    public function deleteWebhook(Tenant $tenant, int $id): void
    {
        WebhookEndpoint::where('idtenant', $tenant->id)->findOrFail($id)->delete();
    }
}
