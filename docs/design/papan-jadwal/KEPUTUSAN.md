# Katalog keputusan prototipe "Papan Jadwal"

Satu baris per perubahan isi (B) atau fitur baru (C) yang tampil di prototipe. Perubahan tampilan saja (A) tidak dicatat di sini; semuanya ikut dibangun.

**Status semua butir: disetujui lewat D-025 (17 Nov 2026), dibangun hanya di branch `agathan-bq-162-papan-jadwal` dan turunannya, belum masuk `integration` sampai Tech Lead memutuskan.** D-025 membuka kembali dua butir D-021 (slot otomatis, kesimpulan di Laporan) khusus untuk branch ini. Angka dan aturan fitur C ada di D-026 (disetujui 17 Nov 2026 kecuali sumber booking, C-08).

Kolom **Jalur** mengikuti `docs/workflow.md` §3. "Berat" berarti menyentuh aturan domain, skema, atau tugas terjadwal, sehingga butuh work item dan pengujian penuh. Kolom **Sesi** merujuk ke rencana di `docs/work/BQ-162-papan-jadwal.md`.

## B: isi halaman berubah (§8 `docs/owner-portal.md`)

| ID | Butir | Layar prototipe | Data | Jalur | Sesi |
|---|---|---|---|---|---|
| B-01 | Beranda: "Perlu Anda" di atas, satu angka utama + pendukung, panduan menjadi kartu, banner dengan satu prioritas | `#/beranda` | Ada (`GetBeranda`) | Ringan | 3 |
| B-02 | Kalender: slot kosong mendatang sebagai ajakan "+ Catat" | `#/kalender/hari` | Ada | Ringan | 3 |
| B-03 | Booking: satu status per baris (titik + teks, lencana hanya untuk yang perlu perhatian), kolom Dibayar berisi jumlah | `#/booking` | Ada | Ringan | 3 |
| B-04 | Pelanggan: segmen "Lama tidak datang"; kartu ringkasan dihapus, hitungan di tab (K8) | `#/pelanggan`, `#/pelanggan/lama` | Ada (dari booking) | Ringan | 3 |
| B-05 | Layanan: catatan "slot terbuka sampai" sekali di atas tabel, pengecualian per baris | `#/layanan` | Ada | Ringan | 4 |
| B-06 | Unit: konflik hanya yang mendatang; yang lampau dilipat | `#/unit` | Ada | Ringan | 4 |
| B-07 | Halaman Booking: pratinjau berisi layanan asli; tab Bagikan | `#/halaman-booking` | Ada | Ringan | 6 |
| B-08 | Pembayaran: tab "Cara kerja" | `#/pembayaran/cara` | Teks tetap | Ringan | 5 |
| B-09 | Laporan: satu kalimat kesimpulan per tab (membuka kembali "saran otomatis" D-021) | `#/laporan` | Dihitung dari angka yang ada | Ringan | 5 |
| B-10 | Istilah: "Tambahan" (add-on), "Halaman depan" (landing page), "Catat booking" (walk-in) | Semua | — | Ringan | 1 |

## C: fitur atau data baru

| ID | Butir | Layar prototipe | Data | Jalur | Sesi |
|---|---|---|---|---|---|
| C-01 | Slot otomatis dari jam buka, tiap malam sampai batas hari ke depan (membuka kembali D-021) | `#/jam-buka` bagian "Slot otomatis" | Baru: pengaturan per tenant/layanan + tugas terjadwal | **Berat** (jadwal, ketersediaan) | 7 |
| C-02 | Selesai otomatis: sesi lewat ditandai selesai setelah X jam bila tidak diubah | `#/pengaturan/kebijakan` | Baru: pengaturan + tugas terjadwal; transisi lewat `BookingState::canTransition` | **Berat** (status booking) | 7 |
| C-03 | Tutup massal: centang banyak dan "Tandai selesai semua" | `#/booking` (Perlu tindakan) | Ada | **Berat** (status booking, banyak baris sekaligus) | 7 |
| C-04 | Laporan: pembanding periode sebelumnya | `#/laporan` | Dihitung ulang di `GetReport` | Ringan | 5 |
| C-05 | Kalender per unit (Pro) | `#/kalender/hari?kolom=unit` | Ada (booking_seats/unit) | Ringan | 3 |
| C-06 | Sapa pelanggan lama lewat WhatsApp (membuka WhatsApp pemilik; tidak ada pesan yang dikirim sistem) | Panel pelanggan, `#/pelanggan/lama` | Ada | Ringan | 3 |
| C-07 | Ringkasan mingguan lewat email | `#/pengaturan/notifikasi` | Baru: preferensi + tugas terjadwal + email | Berat (tugas terjadwal) | 8 |
| C-08 | ~~Sumber booking per kanal~~ **Disembunyikan (D-026 poin 5).** Tidak dibangun: tanpa link `?dari=`, kolom sumber, laporan "Dari mana booking datang" atau filter kanal. Sumber online / walk-in yang sudah ada tetap seperti sekarang | — | — | — | — |
| C-09 | Promo jam sepi | `#/promo`, `#/laporan/okupansi` | Baru: aturan promo per jam | **Berat** (harga, pembayaran) | 8 |
| C-10 | Laporan: hari pesan di muka | `#/laporan/pelanggan` | Ada (created_at vs sesi) | Ringan | 5 |
| C-11 | Laporan: booking yang tidak jadi dibayar | `#/laporan/pendapatan` | Ada (pending yang kedaluwarsa) | Ringan | 5 |

## Tidak termasuk

- Isi fitur per paket di Paket & Tagihan hanya contoh; yang berlaku `docs/product.md` (D-017).
- Yang sengaja tidak diperagakan prototipe (`prototype/HANDOFF.md`): checkout lengkap, unggah berkas, QR sungguhan, simulator yang menghitung. Halaman asli yang sudah ada tetap dipakai, cukup diberi gaya baru.
