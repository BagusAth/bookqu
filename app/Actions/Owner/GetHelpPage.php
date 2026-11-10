<?php

declare(strict_types=1);

namespace App\Actions\Owner;

/**
 * Bantuan (owner-portal.md §8.17, FR-HELP-001): questions grouped by owner page, BookQu's contacts
 * from `config/support.php` (AC-BNT-01) and the way back to the new owner guide on Beranda.
 */
final class GetHelpPage
{
    /** @var array<string, list<array{q: string, a: string}>> */
    private const FAQ = [
        'Beranda' => [
            ['q' => 'Apa saja yang tampil di Beranda?', 'a' => 'Booking dan pendapatan hari ini, hal yang perlu ditindak, jadwal hari ini dan besok, serta link booking Anda.'],
            ['q' => 'Panduan awal tidak muncul lagi. Kenapa?', 'a' => 'Panduan hilang setelah keempat langkahnya selesai: profil bisnis, layanan pertama, jam buka dengan slot terbuka, dan link booking yang dibagikan.'],
        ],
        'Booking' => [
            ['q' => 'Bagaimana mencatat pelanggan yang datang langsung?', 'a' => 'Tekan "+ Booking" di bagian atas, pilih tanggal dan slot kosong, lalu isi nama dan nomor HP pelanggan.'],
            ['q' => 'Kapan booking ditandai selesai?', 'a' => 'Setelah jam booking lewat, tekan "Tandai selesai" di Beranda atau di halaman Booking.'],
        ],
        'Pelanggan' => [
            ['q' => 'Dari mana daftar pelanggan berasal?', 'a' => 'Pelanggan muncul otomatis dari booking. Satu pelanggan dikenali dari email, atau nomor HP bila email kosong.'],
        ],
        'Layanan' => [
            ['q' => 'Kenapa layanan tidak bisa dihapus?', 'a' => 'Layanan yang pernah dibooking hanya bisa dinonaktifkan agar riwayatnya tetap tersimpan.'],
            ['q' => 'Apakah mengubah harga mengubah booking lama?', 'a' => 'Tidak. Harga dan durasi baru hanya berlaku untuk booking baru.'],
        ],
        'Jam Buka & Harga' => [
            ['q' => 'Pelanggan tidak bisa memilih jadwal. Apa yang kurang?', 'a' => 'Pastikan jam buka sudah diatur dan slot sudah dibuka untuk tanggal yang dicari.'],
        ],
        'Pembayaran' => [
            ['q' => 'Kapan saldo bisa dicairkan?', 'a' => 'Setelah pembayaran booking berhasil, saldo bertambah. Ajukan pencairan dari tab Saldo & Pencairan; admin BookQu meninjau dan mentransfer pada jam kerja.'],
            ['q' => 'Berapa biaya platform?', 'a' => 'Biaya platform 0%. Seluruh pembayaran booking yang berhasil masuk ke saldo Anda.'],
        ],
        'Paket & Tagihan' => [
            ['q' => 'Apa yang terjadi bila paket berakhir?', 'a' => 'Booking yang sudah ada tetap bisa dikelola, tetapi booking online dan pembuatan data baru berhenti sampai paket diperpanjang.'],
        ],
    ];

    /**
     * @return array{faq: array<string, list<array{q: string, a: string}>>, contacts: array{whatsapp: string|null, whatsappUrl: string|null, email: string|null, hours: string}, guideUrl: string}
     */
    public function execute(): array
    {
        $whatsapp = preg_replace('/\D+/', '', (string) config('support.whatsapp', '')) ?: null;
        $email = filter_var((string) config('support.email', ''), FILTER_VALIDATE_EMAIL) ?: null;

        return [
            'faq' => self::FAQ,
            'contacts' => [
                'whatsapp' => $whatsapp,
                'whatsappUrl' => $whatsapp !== null ? 'https://wa.me/'.$whatsapp : null,
                'email' => $email,
                'hours' => (string) config('support.hours', ''),
            ],
            'guideUrl' => route('owner.dashboard'),
        ];
    }
}
