<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();
        return [
            'iduser' => User::factory(['role' => 'owner']),
            'namabisnis' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(100, 9999),
            'jenisbisnis' => fake()->randomElement(['Klinik', 'Salon', 'Barbershop', 'Studio']),
            'alamat' => fake()->address(),
            'nomorhp' => fake()->numerify('08##########'),
        ];
    }
}
