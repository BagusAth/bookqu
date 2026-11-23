<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebhookEndpoint> */
class WebhookEndpointFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'idtenant' => Tenant::factory(), 'name' => fake()->word(),
            'url' => 'https://example.test/webhook', 'secret' => bin2hex(random_bytes(32)),
        ];
    }
}
