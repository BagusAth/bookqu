<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantLandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantLandingPage>
 */
class TenantLandingPageFactory extends Factory
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
            'template' => 'modern',
            'headline' => fake()->sentence(4),
            'sections' => array_keys(TenantLandingPage::SECTIONS),
            'banner_asset_id' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['published_at' => now()]);
    }
}
