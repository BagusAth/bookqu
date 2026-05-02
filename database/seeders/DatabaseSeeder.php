<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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

            $serviceCount = rand(2, 5);
            $serviceIds = [];
            $serviceData = [];
            $scheduleMap = [];

            for ($j = 0; $j < $serviceCount; $j++) {
                $price = rand(50000, 250000);
                $service = \App\Models\Service::create([
                    'idtenant' => $tenant->id,
                    'namalayanan' => ucfirst(fake()->words(2, true)),
                    'harga' => $price,
                    'durasi' => rand(45, 120),
                    'deskripsi' => fake()->sentence(),
                ]);

                $serviceIds[] = $service->id;
                $serviceData[$service->id] = [
                    'price' => $price,
                    'name' => $service->namalayanan,
                ];
                $scheduleMap[$service->id] = [];

                $scheduleCount = rand(3, 6);
                for ($k = 0; $k < $scheduleCount; $k++) {
                    $startHour = rand(8, 18);
                    $startTime = sprintf('%02d:00:00', $startHour);
                    $endTime = sprintf('%02d:00:00', min($startHour + 1, 22));
                    $tanggal = Carbon::now()->addDays(rand(1, 14))->toDateString();

                    $scheduleId = DB::table('schedules')->insertGetId([
                        'idtenant' => $tenant->id,
                        'idlayanan' => $service->id,
                        'tanggal' => $tanggal,
                        'jam_mulai' => $startTime,
                        'jam_selesai' => $endTime,
                        'status' => rand(1, 10) > 2 ? 'tersedia' : 'tidak_tersedia',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $scheduleMap[$service->id][] = [
                        'id' => $scheduleId,
                        'tanggal' => $tanggal,
                        'jam' => $startTime,
                    ];
                }
            }

            $bookingCount = rand(6, 14);
            for ($b = 0; $b < $bookingCount; $b++) {
                $serviceId = $serviceIds[array_rand($serviceIds)];
                $scheduleEntry = $scheduleMap[$serviceId][array_rand($scheduleMap[$serviceId])];
                $bookingStatus = fake()->randomElement(['pending', 'paid', 'completed', 'cancelled']);
                $paymentId = null;

                if (in_array($bookingStatus, ['paid', 'completed'], true)) {
                    $paymentId = DB::table('payments')->insertGetId([
                        'idtenant' => $tenant->id,
                        'tipe' => 'booking',
                        'jumlah' => $serviceData[$serviceId]['price'],
                        'status' => 'sukses',
                        'metode' => fake()->randomElement(['transfer', 'ewallet', 'qris']),
                        'external_id' => fake()->uuid(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('bookings')->insert([
                    'idtenant' => $tenant->id,
                    'idlayanan' => $serviceId,
                    'idschedule' => $scheduleEntry['id'],
                    'namapelanggan' => fake()->name(),
                    'nomorhp' => fake()->phoneNumber(),
                    'email' => fake()->unique()->safeEmail(),
                    'tanggalbooking' => $scheduleEntry['tanggal'],
                    'jam' => $scheduleEntry['jam'],
                    'status' => $bookingStatus,
                    'idpayment' => $paymentId,
                    'catatan' => fake()->boolean(30) ? fake()->sentence() : null,
                    'created_at' => now()->subDays(rand(0, 20)),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
