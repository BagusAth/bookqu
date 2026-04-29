<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();

        if (DB::table('plans')->count() === 0) {
            DB::table('plans')->insert([
                [
                    'namapaket' => 'small',
                    'hargabulanan' => 49000,
                    'maxlayanan' => 2,
                    'maxbooking' => 50,
                    'isunlimited' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'namapaket' => 'medium',
                    'hargabulanan' => 129000,
                    'maxlayanan' => 10,
                    'maxbooking' => 300,
                    'isunlimited' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'namapaket' => 'pro',
                    'hargabulanan' => 299000,
                    'maxlayanan' => 0,
                    'maxbooking' => 0,
                    'isunlimited' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if (! DB::table('users')->where('email', 'superadmin@bookqu.test')->exists()) {
            DB::table('users')->insert([
                'namalengkap' => 'Super Admin',
                'email' => 'superadmin@bookqu.test',
                'password' => Hash::make('password'),
                'nomorhp' => '081234567890',
                'role' => 'superadmin',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('users')->where('email', 'admin@bookqu.test')->exists()) {
            DB::table('users')->insert([
                'namalengkap' => 'Admin BookQu',
                'email' => 'admin@bookqu.test',
                'password' => Hash::make('password'),
                'nomorhp' => '081111111111',
                'role' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $owners = User::factory()
            ->count(15)
            ->create([
                'role' => 'owner',
            ]);

        $planIds = DB::table('plans')->pluck('id')->all();

        foreach ($owners as $index => $owner) {
            $namaBisnis = 'Usaha '.$owner->namalengkap;
            $slug = Str::slug($namaBisnis).'-'.Str::lower(Str::random(4));

            $tenantId = DB::table('tenants')->insertGetId([
                'iduser' => $owner->id,
                'namabisnis' => $namaBisnis,
                'slug' => $slug,
                'jenisbisnis' => 'sewa studio',
                'alamat' => 'Jl. Contoh No. '.$index.', Jakarta',
                'nomorhp' => $owner->nomorhp,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $planId = $planIds[array_rand($planIds)];
            $status = $index % 3 === 0 ? 'trial' : 'active';

            DB::table('subscriptions')->insert([
                'idtenant' => $tenantId,
                'idplan' => $planId,
                'status' => $status,
                'trial_berakhir' => $status === 'trial' ? $now->copy()->addDays(7) : null,
                'langganan_mulai' => $status === 'active' ? $now->copy()->subDays(10) : null,
                'langganan_berakhir' => $status === 'active' ? $now->copy()->addDays(20) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($status === 'active') {
                DB::table('payments')->insert([
                    'idtenant' => $tenantId,
                    'tipe' => 'subscription',
                    'jumlah' => 129000,
                    'status' => 'sukses',
                    'metode' => 'midtrans',
                    'external_id' => 'midtrans-'.Str::lower(Str::random(10)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
