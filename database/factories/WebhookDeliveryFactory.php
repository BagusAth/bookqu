<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Scopes\TenantScope;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WebhookDelivery> */
class WebhookDeliveryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            // scope-bypass: use the tenant of the endpoint created by this factory.
            'idtenant' => fn (array $attributes): int => (int) WebhookEndpoint::query()
                ->withoutGlobalScope(TenantScope::class)->findOrFail($attributes['endpoint_id'])->idtenant,
            'endpoint_id' => WebhookEndpoint::factory(), 'event_id' => (string) Str::uuid(), 'event_name' => 'booking.created',
        ];
    }
}
