<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'idtenant' => Tenant::factory(),
            'tipe' => 'booking',
            'jumlah' => 100000,
            'status' => 'pending',
            'metode' => 'midtrans',
            'order_id' => 'ORD-' . fake()->unique()->numerify('######'),
            'expired_at' => now()->addHour(),
            'nama_pembayar' => fake()->name(),
            'email_pembayar' => fake()->safeEmail(),
            'hp_pembayar' => fake()->numerify('08##########'),
        ];
    }
}
