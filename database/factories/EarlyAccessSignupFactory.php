<?php

namespace Database\Factories;

use App\Models\EarlyAccessSignup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EarlyAccessSignup>
 */
class EarlyAccessSignupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'business_name' => fake()->company(),
            'business_type' => fake()->randomElement(['Barbershop', 'Salon', 'Studio foto', 'Klinik', 'Kursus privat']),
            'email' => fake()->unique()->safeEmail(),
            'whatsapp_number' => '08'.fake()->numerify('1#########'),
        ];
    }
}
