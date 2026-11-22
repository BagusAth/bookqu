# Audit prototipe "Papan Jadwal" sebelum dijadikan acuan implementasi

> **Status 17 Nov 2026:** T1–T6 ditutup di sesi 4 (`prototype/README.md`). Keputusan: K-A mulai sebelum rilis, K-B sidebar terang, K-C semua B dan C disetujui (D-025), K-D prototipe masuk repo di branch BQ-162. T7–T10 menjadi bagian implementasi BQ-162.

17 Nov 2026. Pertanyaannya: apakah `prototype/` sudah cukup final untuk diimplementasikan ke portal owner asli (Laravel, Blade, Alpine, Tailwind 4)?

Bahan audit:
- prototipe pada 1366, 1024, 768, 375 dan 1920 px;
- peran Pemilik, Manajer dan Kasir;
- data diganti nama sangat panjang;
- route owner asli (`php artisan route:list`);
- `data-inventory.md`, `plan.md`, `docs/product-decisions.md`.

Pemeriksaannya bisa diulang dengan `alat/tepi.py` (`lebar`, `manajer`, `kasir`, `panjang`).

## Kesimpulan

**Arah desain sudah final. Prototipe sebagai acuan belum final.** Pola utamanya sudah tepat dan layak dijadikan target, yaitu:
- rel hari dan kisi kalender berskala jam;
- tabel lima kolom dengan status titik;
- satu nama "Catat booking";
- slip booking, laci, dialog, dan lembar HP.

Kontras AA dan papan ketik juga sudah lolos. Tailwind 4 di proyek mendukung container query dan subgrid, jadi tidak ada teknik di prototipe yang mustahil dibawa.

Ada empat celah yang harus ditutup dulu (P1). Tanpa itu, pelaksana akan:
- membangun fitur yang belum disetujui;
- merusak tampilan tablet;
- melewatkan keadaan galat yang paling penting untuk booking.

Perkiraan: satu sesi perbaikan prototipe (sesi 4), lalu perencanaan implementasi.

## Yang dipertahankan

- Kisi kalender berskala jam dengan garis "sekarang", slot kosong sebagai ajakan, slot tutup diarsir.
- Booking:
  - tab tugas berhitung;
  - kolom sesi di depan;
  - tombol per baris di "Perlu tindakan";
  - kelompok per tanggal.
- Slip booking: status, waktu besar, pelanggan + WhatsApp, pembayaran, riwayat dengan pelaku.
- Catat booking dalam satu formulir untuk semua pintu, dengan:
  - jam yang dicoret;
  - sisa kursi;
  - saran pelanggan lama;
  - cara bayar.
- Akses per peran sudah sesuai `TenantPermission` (Kasir tanpa uang dan katalog, Manajer tanpa pencairan, Tim, Paket).
- Fitur yang sudah disetujui ada di prototipe:
  - API dan webhook, Google Calendar, Analytics, Meta Pixel (D-019);
  - aturan pemesanan (D-020);
  - kelipatan 5 menit (D-023);
  - "Isi dari jam buka" (D-024);
  - "1/8 peserta" (D-014).

## P1: wajib ditutup sebelum jadi acuan

### T1. Tablet rusak (768–1100 px)

**Bukti**
- Pada 768 px, 10 rute meluap ke kanan, di antaranya:
  - Jam Buka (jam, harga);
  - Unit, Laporan → Pendapatan, Promo;
  - Pengaturan (profil, kebijakan, notifikasi).
- Beranda 768: angka "1 menunggu bayar", "Rp900.000" dan "6 dari 8" menumpuk di atas panel Perlu Anda. Kotak cari menyusut jadi "Cari b".
- Beranda 1024: tombol "Tandai selesai" keluar dari panel jadwal, dan nama terpotong jadi "U…".
- Semua verifikasi sebelumnya hanya memakai 1366 dan 375 px.

**Mengapa penting.** Tablet di meja depan adalah perangkat kerja yang wajar untuk Kasir. Pada lebar ini sidebar 232 px tetap tampil dan isi dipaksa dua kolom.

**Usulan**
- Pada 768–1199 px, sidebar menjadi rel ikon (sekitar 72 px) dengan label di tooltip.
- Beranda satu kolom di bawah sekitar 1100 px.
- Formulir dan simulator harga turun satu kolom.
- Lebar 768 dan 1024 menjadi bagian wajib dari pemeriksaan.

### T2. Keadaan bawaan memamerkan fitur yang belum disetujui

**Bukti**
- Slot otomatis ditunda D-021, tetapi prototipe menjadikannya keadaan normal:
  - panduan Beranda ("Slot dibuka otomatis dari jam buka");
  - keadaan kosong Kalender;
  - ringkasan Jam Buka ("9 dari 10 layanan, dibuka otomatis setiap hari");
  - bagian "Slot otomatis" di tab Jam buka;
  - banner "tidak ikut slot otomatis".
- "Perlu tindakan 3" mengandaikan fitur C "selesai otomatis". Data asli: 80.
- Laporan menampilkan pembanding periode (C) dan kalimat kesimpulan (bertabrakan dengan "saran otomatis di Laporan", D-021).
- Hanya 53 elemen yang berlabel A/B/C, dan labelnya merujuk nomor di `review.md` ("fitur 7"). Pelaksana tidak bisa memakainya tanpa membaca review.

**Mengapa penting.** Codex akan meniru apa yang tampil. Kalau keadaan bawaan berisi C, Codex akan membangun C tanpa D-NNN, padahal AGENTS.md melarangnya. Ini juga menyentuh aturan booking dan jadwal (jalur berat).

**Usulan**
- Sakelar baru "Cakupan": **v1** (bawaan, hanya yang sudah ada atau disetujui) dan **Usulan** (semua butir B/C).
- Dalam mode v1:
  - slot dibuka manual lewat "Buka slot sekaligus" + "Isi dari jam buka";
  - "Perlu tindakan" memakai jumlah realistis (lihat T4);
  - Laporan tanpa pembanding dan tanpa kalimat kesimpulan.
- Tambah `prototype/KEPUTUSAN.md`: satu tabel per layar berisi butir, label A/B/C, status (disetujui D-NNN / menunggu / ditunda D-021), dan apakah masuk v1.

### T3. Keadaan galat yang paling penting untuk booking belum ada

**Bukti.** Tidak ada satu pun keadaan untuk hal-hal berikut:
- slot keburu diambil saat Catat booking atau pindah jadwal (penjaga terakhir INV-BOOK-04);
- gagal simpan atau koneksi putus;
- sesi login kedaluwarsa;
- pengajuan pencairan yang belum terkonfirmasi (koneksi putus; pengajuan tercatat lokal, transfer oleh admin);
- batas booking per bulan tercapai di tengah jalan.

Yang sudah ada hanya galat isian formulir (nama kosong, kode promo ganda, tutup tanggal yang berisi booking).

**Usulan.** Tambah lima keadaan itu:
- pesan dalam bahasa sehari-hari;
- data yang sudah diisi tetap ada;
- untuk slot bentrok, tawarkan jam terdekat yang masih kosong.

Tambahkan juga opsi Keadaan "Galat" di sakelar.

### T4. Data contoh terlalu bersih untuk skala nyata

**Bukti**
- Tenant demo asli punya 80 "Perlu tindakan", 271 booking, dan 10 layanan.
- Paket Medium sampai 20 layanan, Pro tanpa batas.
- Di prototipe, kisi Kalender hari dengan 10 layanan sudah menggulir ke samping, dengan 7 nama terpotong tanpa `title` pada 1366 px ("Umar Wira…", "bayar sebelum 10…").

**Usulan**
- Opsi data "Ramai" di sakelar: 80 tugas, 25 layanan, nama panjang.
- Atas dasar data itu, putuskan:
  - "Tandai selesai" massal untuk semua baris lewat;
  - Kalender hari dengan pilihan kolom (bawaan 6 layanan terpakai hari itu, sisanya lewat "Pilih layanan");
  - judul penuh di setiap blok.

## P2: perbaikan, bisa sejalan dengan implementasi

| # | Celah | Bukti | Usulan |
|---|---|---|---|
| T5 | Tombol ⋯ redup sampai disorot; di layar sentuh tidak ada sorot | Layanan, Unit, daftar | Pada `(pointer: coarse)` tombol selalu penuh |
| T6 | Teks terpotong tanpa cara membaca isinya, bertentangan dengan klaim README | Kalender hari 7, Beranda 3 (subteks jadwal), Kalender minggu 21 pada 768 px | `title` atau dua baris; blok kalender ≤ 60 menit menampilkan nama saja |
| T7 | Pencarian global tidak dirancang: hasil, kosong, papan ketik | Kotak cari tidak berbuat apa-apa; route asli `owner/search` ada | Lembar hasil berkelompok (Booking, Pelanggan), maks. 5 per kelompok, Enter membuka slip |
| T8 | Penyegaran Beranda 60 detik tidak diperagakan | Route asli `owner/dashboard/polling` ada | Tulis di HANDOFF bahwa perilaku ini wajib dipertahankan; tanpa animasi, hanya angka yang berubah |
| T9 | Belum ada tinjauan mata manusia; pembaca layar belum diuji | README "Batas pemeriksaan" | Tinjauan visual oleh Anda dan Product Lead memakai mode v1 setelah sesi 4 |
| T10 | Huruf kedua (Barlow Semi Condensed) menambah satu keluarga font dari Google Fonts | `prototype/index.html` | Boleh (cara pemuatan sama dengan Plus Jakarta Sans); catat sebagai keputusan Tech Lead |

## Keputusan sebelum perencanaan

**Tech Lead (Anda)**

| # | Keputusan | Rekomendasi |
|---|---|---|
| K-A | Kapan implementasi dimulai terhadap rilis v1.0.0 (BQ-158, D-018)? | Setelah rilis. Sebelum rilis hanya penghalang (`plan.md` Tahap 1). Redesain penuh sebelum rilis berarti uji regresi semua `AC-*` diulang. |
| K-B | Sidebar terang (prototipe) atau terong (BQ-161, yang sekarang di integration)? | Terang. Kontras isi lebih tenang, dan terong tetap dipakai untuk tombol utama. Ini mengubah sistem visual BQ-161, jadi catat di `docs/owner-portal.md` §9. |
| K-C | Cakupan implementasi | Hanya mode v1 (A + B yang sudah disetujui). C menunggu D-NNN dan dikerjakan sebagai work item terpisah. |
| K-D | Prototipe masuk repo? | Ya, sebagai acuan baca (misalnya `docs/design/prototype/`), supaya Codex di branch lain bisa membacanya. Tanpa itu, prototipe hanya ada di laptop ini. |

**Product Lead (lewat D-NNN)**

Calon B dari `HANDOFF.md` yang ikut menentukan isi v1:
- urutan Beranda (Perlu Anda di atas, satu angka utama);
- satu status di daftar Booking;
- kartu ringkasan Pelanggan dihapus;
- tab "Cara kerja" di Pembayaran;
- istilah "Tambahan" dan "Halaman depan".

Tanpa persetujuan, butir itu dibangun seperti isi §8 sekarang.

## Langkah yang saya usulkan

1. **Sesi perbaikan prototipe 4** (sekitar satu sesi): T1–T6 plus sakelar Cakupan, keadaan Galat, data Ramai, dan `KEPUTUSAN.md`. Diperiksa dengan `alat/tepi.py` dan `alat/uji3.py` pada 375, 768, 1024, 1366.
2. Anda menjawab K-A sampai K-D. D-NNN untuk butir B ditulis untuk Product Lead.
3. **Rencana implementasi untuk Codex**, dipecah per sesi mengikuti `plan.md` Tahap 2–3:
   - fondasi `x-ui`;
   - lalu bundel Harian, Penyiapan, Uang, Jarang.
   
   Tiap sesi punya berkas, kriteria selesai, perintah uji, dan rute prototipe yang jadi acuan.
