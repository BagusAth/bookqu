<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $ownerBadminton = User::updateOrCreate(
            ['email' => 'badminton.owner@bookqu.test'],
            [
                'namalengkap' => 'Budi Badminton',
                'password' => 'password',
                'nomorhp' => '081234567891',
                'role' => 'owner',
            ]
        );

        $ownerStudio = User::updateOrCreate(
            ['email' => 'studio.owner@bookqu.test'],
            [
                'namalengkap' => 'Nadia Studio',
                'password' => 'password',
                'nomorhp' => '081234567892',
                'role' => 'owner',
            ]
        );

        $tenantBadminton = Tenant::updateOrCreate(
            ['slug' => 'sewa-lapangan-badminton'],
            [
                'iduser' => $ownerBadminton->id,
                'namabisnis' => 'Sewa Lapangan Badminton',
                'jenisbisnis' => 'Sewa Lapangan',
                'alamat' => 'Jl. Gatot Subroto No. 88, Jakarta',
                'nomorhp' => '081234567891',
            ]
        );

        $tenantStudio = Tenant::updateOrCreate(
            ['slug' => 'studio-musik'],
            [
                'iduser' => $ownerStudio->id,
                'namabisnis' => 'Studio Musik Harmoni',
                'jenisbisnis' => 'Studio Musik',
                'alamat' => 'Jl. Jendral Sudirman No. 120, Jakarta',
                'nomorhp' => '081234567892',
            ]
        );

        $badmintonServices = [
            [
                'namalayanan' => 'Lapangan Badminton A',
                'harga' => 85000,
                'durasi' => 60,
                'deskripsi' => 'Lapangan standar kompetisi dengan lantai vinyl anti-slip dan pencahayaan terang.',
                'is_active' => true,
                'is_popular' => true,
                'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Lapangan+Badminton+A',
                'kapasitas' => 6,
                'satuan_harga' => 'jam',
                'satuan_durasi' => 'menit',
            ],
            [
                'namalayanan' => 'Lapangan Badminton B',
                'harga' => 75000,
                'durasi' => 60,
                'deskripsi' => 'Lapangan latihan dengan akses tribun kecil dan sirkulasi udara nyaman.',
                'is_active' => true,
                'is_popular' => false,
                'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Lapangan+Badminton+B',
                'kapasitas' => 6,
                'satuan_harga' => 'jam',
                'satuan_durasi' => 'menit',
            ],
            [
                'namalayanan' => 'Lapangan Badminton VIP',
                'harga' => 120000,
                'durasi' => 60,
                'deskripsi' => 'Lapangan premium dengan area lounge, ruang ganti, dan fasilitas tambahan.',
                'is_active' => true,
                'is_popular' => false,
                'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Lapangan+Badminton+VIP',
                'kapasitas' => 8,
                'satuan_harga' => 'jam',
                'satuan_durasi' => 'menit',
            ],
        ];

        foreach ($badmintonServices as $service) {
            Service::updateOrCreate(
                ['idtenant' => $tenantBadminton->id, 'namalayanan' => $service['namalayanan']],
                array_merge($service, ['idtenant' => $tenantBadminton->id])
            );
        }

        $studioServices = [
            [
                'namalayanan' => 'Studio Rekaman A',
                'harga' => 250000,
                'durasi' => 120,
                'deskripsi' => 'Paket rekaman 2 jam dengan treatment akustik premium dan engineer pendamping.',
                'is_active' => true,
                'is_popular' => true,
                'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Studio+Rekaman+A',
                'kapasitas' => 5,
                'satuan_harga' => 'sesi',
                'satuan_durasi' => 'menit',
            ],
            [
                'namalayanan' => 'Studio Rekaman B',
                'harga' => 180000,
                'durasi' => 120,
                'deskripsi' => 'Studio rekaman dengan soundproof wall dan monitor mixing berkualitas.',
                'is_active' => true,
                'is_popular' => false,
                'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Studio+Rekaman+B',
                'kapasitas' => 4,
                'satuan_harga' => 'sesi',
                'satuan_durasi' => 'menit',
            ],
            [
                'namalayanan' => 'Ruang Latihan Band',
                'harga' => 120000,
                'durasi' => 60,
                'deskripsi' => 'Ruang latihan lengkap dengan drum, ampli gitar, dan mixer monitoring.',
                'is_active' => true,
                'is_popular' => false,
                'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Ruang+Latihan+Band',
                'kapasitas' => 6,
                'satuan_harga' => 'jam',
                'satuan_durasi' => 'menit',
            ],
            [
                'namalayanan' => 'Vocal Booth',
                'harga' => 60000,
                'durasi' => 60,
                'deskripsi' => 'Booth vokal untuk take cepat dengan mic condenser dan pop filter.',
                'is_active' => true,
                'is_popular' => false,
                'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Vocal+Booth',
                'kapasitas' => 2,
                'satuan_harga' => 'jam',
                'satuan_durasi' => 'menit',
            ],
        ];

        foreach ($studioServices as $service) {
            Service::updateOrCreate(
                ['idtenant' => $tenantStudio->id, 'namalayanan' => $service['namalayanan']],
                array_merge($service, ['idtenant' => $tenantStudio->id])
            );
        }
    }
}
