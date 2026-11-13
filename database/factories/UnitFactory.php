<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'idtenant' => Tenant::factory(),
            'name' => 'Lapangan '.fake()->unique()->numberBetween(1, 9999),
            'type' => 'Lapangan',
            'kapasitas' => 1,
            'is_active' => true,
        ];
    }
}
