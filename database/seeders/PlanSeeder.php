<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'namapaket' => 'small',
                'hargabulanan' => 149000,
                'maxlayanan' => 5,
                'maxbooking' => 300,
                'isunlimited' => false,
            ],
            [
                'namapaket' => 'medium',
                'hargabulanan' => 299000,
                'maxlayanan' => 0,
                'maxbooking' => 500,
                'isunlimited' => false,
            ],
            [
                'namapaket' => 'pro',
                'hargabulanan' => 499000,
                'maxlayanan' => 0,
                'maxbooking' => 0,
                'isunlimited' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['namapaket' => $plan['namapaket']],
                $plan
            );
        }
    }
}
