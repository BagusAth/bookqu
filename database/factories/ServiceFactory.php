<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'idtenant' => Tenant::factory(),
            'namalayanan' => fake()->words(2, true),
            'harga' => fake()->randomElement([50000, 100000, 150000, 200000]),
            'durasi' => 60,
            'deskripsi' => fake()->sentence(),
            'is_active' => true,
            'satuan_harga' => 'sesi',
            'satuan_durasi' => 'menit',
        ];
    }
}
