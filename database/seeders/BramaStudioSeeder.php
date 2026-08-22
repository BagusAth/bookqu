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

class BramaStudioSeeder extends Seeder
{
    /**
     * Seed data dummy untuk Brama Studio.
     */
    public function run(): void
    {
        // ── Plans ──
        $planmedium = Plan::where('namapaket', 'medium')->first();
        if (!$planmedium) {
            $planmedium = Plan::create([
                'namapaket' => 'medium',
                'hargabulanan' => 199000,
                'maxlayanan' => 15,
                'maxbooking' => 500,
                'isunlimited' => false,
            ]);
        }

        // ── Tenant ──
        $tenant = Tenant::find(5);
        
        if (!$tenant) {
            $this->command->error('Tenant with ID 5 not found!');
            return;
        }

        // ── Subscription (Trial) ──
        Subscription::updateOrCreate(
            ['idtenant' => $tenant->id],
            [
                'idplan' => $planmedium->id,
                'status' => 'trial',
                'trial_berakhir' => Carbon::now()->addDays(14),
                'langganan_mulai' => null,
                'langganan_berakhir' => null,
            ]
        );

        // ── Services ──
        $daftarlayanan = [
            ['namalayanan' => 'Studio Foto Basic (1 Jam)', 'harga' => 150000, 'durasi' => 60, 'deskripsi' => 'Sewa studio foto dengan background standar (putih/hitam/abu-abu). Include 2 lighting.', 'satuan_harga' => 'jam', 'satuan_durasi' => 'menit', 'is_active' => true, 'is_popular' => true, 'kapasitas' => 5, 'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Studio+Foto+Basic'],
            ['namalayanan' => 'Studio Foto Premium (1 Jam)', 'harga' => 250000, 'durasi' => 60, 'deskripsi' => 'Sewa studio foto dengan background tematik dan lighting premium. Include asisten.', 'satuan_harga' => 'jam', 'satuan_durasi' => 'menit', 'is_active' => true, 'is_popular' => true, 'kapasitas' => 10, 'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Studio+Foto+Premium'],
            ['namalayanan' => 'Studio Podcast (2 Jam)', 'harga' => 300000, 'durasi' => 120, 'deskripsi' => 'Studio podcast dengan 4 mic Rode, mixer, dan 3 kamera set.', 'satuan_harga' => 'sesi', 'satuan_durasi' => 'menit', 'is_active' => true, 'is_popular' => false, 'kapasitas' => 4, 'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Studio+Podcast'],
            ['namalayanan' => 'Studio Video Green Screen (1 Jam)', 'harga' => 200000, 'durasi' => 60, 'deskripsi' => 'Studio khusus video shooting dengan green screen full body.', 'satuan_harga' => 'jam', 'satuan_durasi' => 'menit', 'is_active' => true, 'is_popular' => false, 'kapasitas' => 8, 'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Video+Green+Screen'],
            ['namalayanan' => 'Family Photoshoot (1 Sesi)', 'harga' => 500000, 'durasi' => 60, 'deskripsi' => 'Paket foto keluarga termasuk fotografer profesional dan 10 edit foto.', 'satuan_harga' => 'sesi', 'satuan_durasi' => 'menit', 'is_active' => true, 'is_popular' => true, 'kapasitas' => 6, 'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Family+Photoshoot'],
            ['namalayanan' => 'Graduation Photoshoot (1 Sesi)', 'harga' => 400000, 'durasi' => 60, 'deskripsi' => 'Paket foto wisuda. Dapat dipinjamkan properti standar.', 'satuan_harga' => 'sesi', 'satuan_durasi' => 'menit', 'is_active' => true, 'is_popular' => false, 'kapasitas' => 4, 'image_url' => 'https://placehold.co/1200x675/EEF2FF/4F46E5?text=Graduation+Photoshoot'],
        ];

        $layananids = [];
        foreach ($daftarlayanan as $datanya) {
            $layanan = Service::updateOrCreate(
                ['idtenant' => $tenant->id, 'namalayanan' => $datanya['namalayanan']],
                array_merge($datanya, ['idtenant' => $tenant->id])
            );
            $layananids[] = $layanan->id;
        }

        // ── Schedules & Bookings & Payments ──
        $namanamapelanggan = [
            'Arif Hakim', 'Bunga Citra', 'Caca Marica', 'Deni Setiawan',
            'Eka Saputri', 'Fajar Ramadhan', 'Gita Gutawa', 'Hendi Hermawan',
            'Irma Suryani', 'Joko Susilo', 'Kiki Amalia', 'Lukman Hakim',
            'Maya Wulan', 'Nadia Safira', 'Oki Setiana', 'Putra Permana',
            'Rini Yulianti', 'Sandi Nugraha', 'Tari Lestari', 'Umar Wirahadi',
        ];

        $statusbooking = ['pending', 'paid', 'completed', 'cancelled'];
        $metodepembayaran = ['transfer_bank', 'ewallet', 'qris', 'kartu_kredit'];
        
        // Clear old bookings for this tenant to avoid massive duplicates if run multiple times
        $oldSchedules = Schedule::where('idtenant', $tenant->id)->pluck('id');
        if ($oldSchedules->count() > 0) {
            Booking::whereIn('idschedule', $oldSchedules)->delete();
            Payment::where('idtenant', $tenant->id)->delete();
            Schedule::where('idtenant', $tenant->id)->delete();
        }

        // Generate data untuk 7 bulan terakhir
        for ($bulan = 6; $bulan >= 0; $bulan--) {
            $tanggalawal = Carbon::now()->subMonths($bulan)->startOfMonth();
            $tanggalakhir = $bulan === 0
                ? Carbon::now()
                : Carbon::now()->subMonths($bulan)->endOfMonth();

            $jumlahinbulan = $bulan === 0 ? rand(80, 120) : rand(120, 200);

            for ($i = 0; $i < $jumlahinbulan; $i++) {
                $tanggalrandom = Carbon::createFromTimestamp(
                    rand($tanggalawal->timestamp, $tanggalakhir->timestamp)
                );

                $idlayanan = $layananids[array_rand($layananids)];
                $layananobj = Service::find($idlayanan);

                // Create schedule
                $jammulai = sprintf('%02d:00', rand(8, 20));
                $jamselesai = sprintf('%02d:00', min(intval($jammulai) + ceil($layananobj->durasi / 60), 22));

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
            }
        }
    }
}
