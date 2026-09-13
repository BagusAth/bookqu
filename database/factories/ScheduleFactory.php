<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        return [
            'idtenant' => Tenant::factory(),
            'idlayanan' => Service::factory(),
            'tanggal' => now()->addDays(2)->format('Y-m-d'),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ];
    }
}
