<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OwnerApiKey;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OwnerApiKey> */
class OwnerApiKeyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'idtenant' => Tenant::factory(), 'name' => fake()->word(),
            'prefix' => 'bq_'.fake()->lexify('?????????'), 'digest' => hash('sha256', bin2hex(random_bytes(32))),
        ];
    }
}
