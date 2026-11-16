<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OwnerIntegration;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OwnerIntegration> */
class OwnerIntegrationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['idtenant' => Tenant::factory()];
    }
}
