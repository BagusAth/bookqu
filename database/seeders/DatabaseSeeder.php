<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $planSmall = Plan::create([
            'namapaket' => 'small',
            'hargabulanan' => 49000,
            'maxlayanan' => 2,
            'maxbooking' => 50,
            'isunlimited' => false,
        ]);

        $planMedium = Plan::create([
            'namapaket' => 'medium',
            'hargabulanan' => 129000,
            'maxlayanan' => 10,
            'maxbooking' => 300,
            'isunlimited' => false,
        ]);

        $planPro = Plan::create([
            'namapaket' => 'pro',
            'hargabulanan' => 299000,
            'maxlayanan' => 9999,
            'maxbooking' => 9999,
            'isunlimited' => true,
        ]);

        User::create([
            'namalengkap' => 'Super Admin BookQu',
            'email' => 'admin@bookqu.com',
            'password' => Hash::make('password123'),
            'nomorhp' => '08111111111',
            'role' => 'superadmin',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $owner = User::create([
                'namalengkap' => 'Owner Bisnis '.$i,
                'email' => 'owner'.$i.'@gmail.com',
                'password' => Hash::make('password123'),
                'nomorhp' => '0822222222'.$i,
                'role' => 'owner',
            ]);

            $tenant = Tenant::create([
                'iduser' => $owner->id,
                'namabisnis' => 'Bisnis Hebat '.$i,
                'slug' => 'bisnis-hebat-'.$i,
                'jenisbisnis' => 'Futsal',
                'alamat' => 'Jl. Kebenaran No. '.$i,
                'nomorhp' => '0833333333'.$i,
            ]);

            Subscription::create([
                'idtenant' => $tenant->id,
                'idplan' => $i % 2 === 0 ? $planPro->id : $planMedium->id,
                'status' => $i % 3 === 0 ? 'trial' : 'active',
                'trial_berakhir' => Carbon::now()->addDays(7),
                'langganan_mulai' => Carbon::now()->subDays(rand(1, 10)),
                'langganan_berakhir' => Carbon::now()->addDays(20),
            ]);
        }
    }
}
