<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DashboardSeeder extends Seeder
{
    /**
     * Seed data dummy untuk dashboard owner.
     */
    public function run(): void
    {
        // ── Plans ──
        $plansmall = Plan::create([
            'namapaket' => 'small',
            'hargabulanan' => 99000,
            'maxlayanan' => 5,
            'maxbooking' => 100,
            'isunlimited' => false,
        ]);

        $planmedium = Plan::create([
            'namapaket' => 'medium',
            'hargabulanan' => 199000,
            'maxlayanan' => 15,
            'maxbooking' => 500,
            'isunlimited' => false,
        ]);

        Plan::create([
            'namapaket' => 'pro',
            'hargabulanan' => 399000,
            'maxlayanan' => 50,
            'maxbooking' => 9999,
            'isunlimited' => true,
        ]);

        // ── User Owner ──
        $userowner = User::create([
            'namalengkap' => 'Alex Pratama',
            'email' => 'alex@bookqu.test',
            'password' => Hash::make('password'),
            'nomorhp' => '081234567890',
            'role' => 'owner',
        ]);

        // ── Tenant ──
        $tenant = Tenant::create([
            'iduser' => $userowner->id,
            'namabisnis' => 'ZenFit Studio',
            'slug' => 'zenfit-studio',
            'jenisbisnis' => 'Fitness & Wellness',
            'alamat' => 'Jl. Sudirman No. 42, Jakarta Selatan',
            'nomorhp' => '081234567890',
        ]);

        // ── Subscription (Trial) ──
        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan' => $planmedium->id,
            'status' => 'trial',
            'trial_berakhir' => Carbon::now()->addDays(7),
            'langganan_mulai' => null,
            'langganan_berakhir' => null,
        ]);

        // ── Services (24 active programs) ──
        $daftarlayanan = [
            ['namalayanan' => 'Premium Yoga Flow', 'harga' => 150000, 'durasi' => 60, 'deskripsi' => 'Sesi yoga premium dengan instruktur berpengalaman'],
            ['namalayanan' => 'Beginner Pilates', 'harga' => 120000, 'durasi' => 45, 'deskripsi' => 'Program pilates untuk pemula'],
            ['namalayanan' => 'HIIT Cardio Max', 'harga' => 175000, 'durasi' => 30, 'deskripsi' => 'Latihan kardio intensitas tinggi'],
            ['namalayanan' => 'Morning Yoga Session', 'harga' => 100000, 'durasi' => 60, 'deskripsi' => 'Yoga pagi untuk memulai hari dengan energi'],
            ['namalayanan' => 'Advanced Pilates', 'harga' => 200000, 'durasi' => 60, 'deskripsi' => 'Pilates tingkat lanjut dengan peralatan lengkap'],
            ['namalayanan' => 'Meditation & Breathwork', 'harga' => 80000, 'durasi' => 45, 'deskripsi' => 'Sesi meditasi dan teknik pernapasan'],
            ['namalayanan' => 'Functional Training', 'harga' => 160000, 'durasi' => 45, 'deskripsi' => 'Latihan fungsional untuk kekuatan sehari-hari'],
            ['namalayanan' => 'Zumba Dance Fitness', 'harga' => 130000, 'durasi' => 60, 'deskripsi' => 'Olahraga sambil menari dengan irama Zumba'],
            ['namalayanan' => 'Strength & Conditioning', 'harga' => 180000, 'durasi' => 50, 'deskripsi' => 'Program kekuatan dan pengondisian tubuh'],
            ['namalayanan' => 'Prenatal Yoga', 'harga' => 140000, 'durasi' => 45, 'deskripsi' => 'Yoga khusus untuk ibu hamil'],
            ['namalayanan' => 'Boxing Fitness', 'harga' => 190000, 'durasi' => 45, 'deskripsi' => 'Latihan tinju untuk kebugaran'],
            ['namalayanan' => 'Stretching Recovery', 'harga' => 90000, 'durasi' => 30, 'deskripsi' => 'Sesi peregangan dan pemulihan otot'],
            ['namalayanan' => 'CrossFit Basics', 'harga' => 200000, 'durasi' => 60, 'deskripsi' => 'Dasar-dasar CrossFit untuk pemula'],
            ['namalayanan' => 'Aerial Yoga', 'harga' => 220000, 'durasi' => 60, 'deskripsi' => 'Yoga dengan hammock di udara'],
            ['namalayanan' => 'Body Combat', 'harga' => 160000, 'durasi' => 45, 'deskripsi' => 'Program body combat untuk kardio dan kekuatan'],
            ['namalayanan' => 'Power Cycling', 'harga' => 150000, 'durasi' => 40, 'deskripsi' => 'Kelas sepeda statis dengan intensitas tinggi'],
            ['namalayanan' => 'Kids Yoga', 'harga' => 85000, 'durasi' => 30, 'deskripsi' => 'Yoga menyenangkan untuk anak-anak'],
            ['namalayanan' => 'Senior Fitness', 'harga' => 95000, 'durasi' => 40, 'deskripsi' => 'Program kebugaran khusus lansia'],
            ['namalayanan' => 'TRX Suspension', 'harga' => 170000, 'durasi' => 45, 'deskripsi' => 'Latihan TRX suspension training'],
            ['namalayanan' => 'Hot Yoga', 'harga' => 200000, 'durasi' => 60, 'deskripsi' => 'Yoga dalam ruangan bersuhu tinggi'],
            ['namalayanan' => 'Barre Workout', 'harga' => 140000, 'durasi' => 50, 'deskripsi' => 'Latihan barre yang menggabungkan balet dan fitness'],
            ['namalayanan' => 'Aqua Aerobics', 'harga' => 130000, 'durasi' => 45, 'deskripsi' => 'Aerobik dalam air'],
            ['namalayanan' => 'Kickboxing', 'harga' => 180000, 'durasi' => 45, 'deskripsi' => 'Latihan kickboxing untuk kebugaran'],
            ['namalayanan' => 'Mobility Flow', 'harga' => 110000, 'durasi' => 30, 'deskripsi' => 'Sesi mobilitas dan fleksibilitas'],
        ];

        $layananids = [];
        foreach ($daftarlayanan as $datanya) {
            $layanan = Service::create(array_merge($datanya, ['idtenant' => $tenant->id]));
            $layananids[] = $layanan->id;
        }

        // ── Schedules & Bookings & Payments ──
        $namanamapelanggan = [
            'Budi Santoso', 'Siti Rahayu', 'Ahmad Fauzi', 'Dewi Kartika',
            'Rudi Hermawan', 'Maya Putri', 'Andi Wijaya', 'Rina Marlina',
            'Doni Saputra', 'Lestari Handayani', 'Fajar Nugroho', 'Indah Permata',
            'Yoga Pratama', 'Anisa Safitri', 'Hendra Gunawan', 'Putri Ayu',
            'Rizki Ramadhan', 'Novia Sari', 'Bagus Setiawan', 'Tika Wulandari',
        ];

        $statusbooking = ['pending', 'paid', 'completed', 'cancelled'];
        $metodepembayaran = ['transfer_bank', 'ewallet', 'qris', 'kartu_kredit'];
        $jumlahbooking = 0;

        // Generate data untuk 7 bulan terakhir
        for ($bulan = 6; $bulan >= 0; $bulan--) {
            $tanggalawal = Carbon::now()->subMonths($bulan)->startOfMonth();
            $tanggalakhir = $bulan === 0
                ? Carbon::now()
                : Carbon::now()->subMonths($bulan)->endOfMonth();

            $jumlahinbulan = $bulan === 0 ? rand(150, 200) : rand(120, 250);

            for ($i = 0; $i < $jumlahinbulan; $i++) {
                $tanggalrandom = Carbon::createFromTimestamp(
                    rand($tanggalawal->timestamp, $tanggalakhir->timestamp)
                );

                $idlayanan = $layananids[array_rand($layananids)];
                $layananobj = Service::find($idlayanan);

                // Create schedule
                $jammulai = sprintf('%02d:00', rand(7, 20));
                $jamselesai = sprintf('%02d:00', min(intval($jammulai) + 1, 21));

                $schedule = Schedule::create([
                    'idtenant' => $tenant->id,
                    'idlayanan' => $idlayanan,
                    'tanggal' => $tanggalrandom->format('Y-m-d'),
                    'jam_mulai' => $jammulai,
                    'jam_selesai' => $jamselesai,
                    'status' => 'tersedia',
                ]);

                $statusnya = $statusbooking[array_rand($statusbooking)];
                $namapelanggan = $namanamapelanggan[array_rand($namanamapelanggan)];

                // Create payment for paid/completed bookings
                $idpayment = null;
                if (in_array($statusnya, ['paid', 'completed'])) {
                    $payment = Payment::create([
                        'idtenant' => $tenant->id,
                        'tipe' => 'booking',
                        'jumlah' => $layananobj->harga,
                        'status' => 'sukses',
                        'metode' => $metodepembayaran[array_rand($metodepembayaran)],
                        'external_id' => 'PAY-' . Str::random(12),
                    ]);
                    $payment->created_at = $tanggalrandom;
                    $payment->save();
                    $idpayment = $payment->id;
                }

                $booking = Booking::create([
                    'idtenant' => $tenant->id,
                    'idlayanan' => $idlayanan,
                    'idschedule' => $schedule->id,
                    'namapelanggan' => $namapelanggan,
                    'nomorhp' => '08' . rand(1000000000, 9999999999),
                    'email' => strtolower(str_replace(' ', '.', $namapelanggan)) . '@email.com',
                    'tanggalbooking' => $tanggalrandom->format('Y-m-d'),
                    'jam' => $jammulai,
                    'status' => $statusnya,
                    'idpayment' => $idpayment,
                    'catatan' => null,
                ]);
                $booking->created_at = $tanggalrandom;
                $booking->updated_at = $tanggalrandom;
                $booking->save();

                $jumlahbooking++;
            }
        }
    }
}
