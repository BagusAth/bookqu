<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'idtenant' => Tenant::factory(),
            'idlayanan' => Service::factory(),
            'idschedule' => Schedule::factory(),
            'namapelanggan' => fake()->name(),
            'nomorhp' => fake()->numerify('08##########'),
            'email' => fake()->safeEmail(),
            'tanggalbooking' => now()->addDays(2)->format('Y-m-d'),
            'jam' => '10:00:00',
            'status' => 'pending',
            'booking_code' => 'BKQ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8)),
            'cancellation_token' => Str::random(64),
            'reschedule_token' => Str::random(64),
        ];
    }
}
