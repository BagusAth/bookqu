# Temuan pengujian branch `agathan-bq-162-papan-jadwal` (18 Nov 2026)

Status pengujian awal 18 Nov 2026: **selesai**. Sesi 1 berjalan siang hari dan dihentikan; sesi 2 berjalan malam hari dan menuntaskan semua sisa pekerjaan. Hanya pass screen reader manual yang tetap butuh orang (lihat bagian 5).
Commit yang diuji: `22e1318` (tip `origin/agathan-bq-162-papan-jadwal`, tidak berubah di antara dua sesi).

## Status tindak lanjut per 19 November 2026

Implementasi **21 bagian Developer A selesai** pada branch `agathan-bq-162-papan-jadwal`, sampai commit `379dfd7`. Status Developer B belum diverifikasi dalam sesi ini. T-15, T-17, dan T-29 memiliki bagian A dan B yang dilacak terpisah.

Bukti terbaru tercatat pada [BQ-163](../BQ-163-owner-automation-and-booking-follow-up.md) dan [BQ-162](../BQ-162-papan-jadwal.md). Suite penuh SQLite lulus 1.020 tes / 6.695 assertion. Suite MySQL terkait lulus 35 tes / 147 assertion. Regresi batas batch setelahnya lulus di kedua database. PHPStan, Pint pada file berubah, dan pemeriksaan integritas dokumentasi lulus. Build frontend lulus pada tahap perbaikan UI.

Crawl browser setelah perbaikan, pemeriksaan NVDA/VoiceOver, pemeriksaan zona waktu produksi, serta verifikasi setelah pekerjaan B digabung masih perlu dijalankan saat verifikasi rilis. Status selesai di sini merujuk pada implementasi dan bukti otomatis yang disebutkan.

Rincian hasil per ID tersedia di [pembagian dan status perbaikan](pembagian-perbaikan-dev-a-b.md). Temuan, nomor baris, dan hasil pengujian pada bagian 1–4 di bawah berasal dari commit `22e1318`.

## 1. Lingkungan uji

- Worktree: `.claude/worktrees/agathan-bg-162-testing-docs-68abec`, branch lokal `claude/agathan-bg-162-testing-docs-68abec` = `22e1318`.
- PHP 8.4.12 (GD ada), Node 20, SQLite file `database/papan_test.sqlite` (hasil `migrate:fresh --seed`, tidak di-commit).
- App: `php artisan serve --host=localhost --port=8020` (`.env` worktree: `APP_URL=http://localhost:8020`, `DB_CONNECTION=sqlite`, `MAIL_MAILER=log`).
- **Wajib**: shell Claude mengekspor variabel `.env` utama (`DB_CONNECTION=mysql`, `DB_DATABASE=bookqu`, …) yang mengalahkan `.env` worktree. Jalankan setiap perintah artisan dengan variabel itu di-unset, contoh:
  `env $(env | grep -oE "^(APP|SESSION|DB|CACHE|QUEUE|MAIL|LOG|MIDTRANS|SINGAPAY|PAYMENT|MODULE)[A-Z_]*" | sed 's/^/-u /') php artisan …`
  dan cek target dengan `php artisan db:show` sebelum migrate/seed.
- Akun uji (password `password`): Pemilik `brama@bookqu.test` (tenant 4). Manajer `manajer@bookqu.test` dan Kasir `kasir@bookqu.test` dibuat manual di DB SQLite. Email keduanya diverifikasi manual pada sesi 2.

## 2. Hasil pemeriksaan otomatis awal pada 22e1318

| Pemeriksaan | Hasil |
|---|---|
| `php artisan test` (SQLite) | **1008 lulus, 4 gagal** (6661 assertion, 101 s) |
| PHPStan | **1 error**: `OwnerScheduleController.php:75` (`namalayanan` pada `Model` generik) |
| Pint `--test` | 35 file gagal format, **semuanya file lama** (tidak ada yang disentuh BQ-162) |
| `npm run build` | Lulus |
| Migrasi baru (`2026_11_17_*`) | `rollback --step=2` lalu `migrate` lulus |
| `bookings:complete-ended` | Jalan, 123 booking `paid` lewat ditandai selesai, log `actor_role=system` |
| `slots:open-automatic` | Jalan, 182 slot dibuat untuk 1 layanan sampai +30 hari; run kedua 0 (idempoten) |
| `owners:weekly-summary` | Jalan, 5 email antre; isi angka benar (lihat T-14) |
| Crawl Edge headless (Pemilik, 66 URL × 375/768/1366) | 0 HTTP error, 0 error console, 3 overflow di 768 px, 1 gambar rusak |
| Crawl Manajer + Kasir (sesi 2, 375/768/1024/1366, 356 cek) | 0 error console. Akses per role sesuai `owner-portal.md` §5. Overflow 768 px sama dengan T-17 |
| Crawl Pemilik 1024 px (sesi 2, 18 halaman) | 0 error, 0 overflow |
| Suite MySQL (`phpunit.mysql.xml`, DB `bookqu_testing`) | 1031 test, 6729 assertion, **4 gagal (sama dengan SQLite)**, 1 skipped. DB utama `bookqu` tidak tersentuh (792 booking sebelum dan sesudah) |

4 test yang gagal:

1. `CoreFlowIntegrationTest::test_owner_only_sees_own_tenant_bookings`
2. `OwnerUiAuditTest::test_owner_pages_render_when_optional_service_or_account_relations_are_missing`
3. `OwnerPortalWorkflowTest::test_csv_export_is_plan_gated_and_neutralizes_formula_cells`
4. `ArchitectureRulesTest::test_scope_bypasses_carry_a_reason_comment`

Penyebab 1–3: halaman Booking sekarang membuka tab "Perlu tindakan" bila tidak ada `tab` (lihat T-02). Penyebab 4: lihat T-09.

## 3. Daftar temuan

Prioritas: **P1** = salah data/perilaku, perbaiki sebelum merge; **P2** = mengganggu pengguna atau berisiko; **P3** = kosmetik/kualitas.

### P1

**T-01 Simpan "Jam buka" menimpa pilihan "Buka slot otomatis" semua layanan.**

> **Tindak lanjut A — selesai (19 Nov 2026).** Pilihan per layanan dipertahankan saat simpan jam buka. Pilihan massal harus dipilih secara eksplisit. Commit `01da216`.
- Reproduksi: Layanan → aktifkan buka slot otomatis untuk 1 layanan saja → Jam Buka & Harga → tab Jam buka (switch tampil aktif karena `auto_open > 0`) → klik "Simpan jam buka" tanpa mengubah apa pun → teks berubah dari "1 dari 10 layanan ikut" menjadi "10 dari 10 layanan ikut". Malam berikutnya `slots:open-automatic` membuka slot untuk semua layanan.
- Penyebab: `public/js/owner-schedule.js:62` selalu mengirim `auto_open_slots: true/false`; `SaveOpeningHours.php:54` lalu meng-update semua layanan aktif. Switch di `resources/views/owner/schedule/hours.blade.php:7` dicentang bila minimal satu layanan ikut.
- Saran: kirim `auto_open_slots` hanya bila switch benar-benar diubah pengguna (atau tampilkan status campuran "Sebagian" dan jangan kirim nilainya), dan tambah test untuk kasus 1 dari N.

**T-02 Ekspor CSV Booking tanpa `tab` hanya berisi "Perlu tindakan".**

> **Tindak lanjut A — selesai (19 Nov 2026).** Ekspor tanpa tab memakai Semua, termasuk nilai tab kosong. Halaman daftar tetap membuka Perlu tindakan. Commit `2d26195, 9c1a93f`.
- Reproduksi: buka `/owner/bookings/export` → 2 baris data; `/owner/bookings/export?tab=all` → 259 baris.
- Penyebab: `OwnerBookingController::selectedTab()` (baris 189–195) sekarang default ke `action`, dan `export()` (baris 151) memakai fungsi yang sama.
- Saran: ekspor default ke `all` bila `tab` tidak ada (tombol di UI tetap mengirim tab aktif). Lalu perbarui tiga test di atas: untuk daftar Booking, kirim `?tab=all` atau ubah assertion ke perilaku baru, dan sebutkan alasannya di pesan commit (aturan BQ-162 no. 3).

**T-03 Kolom "Terakhir datang" di Pelanggan berisi tanggal masa depan.**
- Reproduksi: Pelanggan → Eka Saputri: "Terakhir datang 2 Des", "Berikutnya 19 Nov" (hari ini 18 Nov).
- Penyebab: `ListCustomers.php:158` `MAX(tanggalbooking)` mencakup booking yang akan datang (dan mungkin yang dibatalkan).
- Saran: hitung hanya sesi yang sudah lewat dengan status yang menempati slot/selesai. Segmen "Lama tidak datang" dan pengurutan "Booking terakhir" memakai kolom yang sama, jadi ikut diperbaiki.

**T-04 Beranda menyembunyikan seluruh tampilan harian bagi tenant lama.**
- Reproduksi: login Pemilik tenant yang sudah punya 258 booking → Beranda hanya menampilkan "Siapkan bisnis Anda 3 dari 4 langkah". Angka, Perlu Anda, Jadwal hari ini dan Besok hilang sampai pemilik menekan "Salin link"/WhatsApp.
- Penyebab: `GetBeranda::guide()` (baris 216–243) memakai `booking_link_shared_at`; migrasi `2026_11_09_164701_add_owner_guide_columns_to_tenants_table.php` tidak mem-backfill tenant yang sudah aktif. Selain itu, bila clipboard gagal (terjadi di pane browser), `copy()` di `public/js/owner-ui.js:353` tidak memanggil `mark()`.
- Saran: backfill `owner_guide_completed_at` untuk tenant yang sudah punya booking (atau anggap langkah 4 selesai bila sudah ada booking online), tambah "Lewati panduan", dan tetap tandai langkah 4 walau clipboard gagal.

**T-05 Seeder tidak membuat `tenant_members`, sehingga pemilik hasil seed mendapat 403.**
- Reproduksi: `migrate:fresh --seed` → login `brama@bookqu.test` → `/owner/dashboard` 403 "Tidak punya akses".
- Penyebab: `DatabaseSeeder`/`BramaStudioSeeder` tidak memanggil `App\Actions\Team\CreateOwnerMembership`. Backfill hanya ada di migrasi `create_tenant_members_table`, yang jalan sebelum seeder.
- Saran: panggil `CreateOwnerMembership` untuk setiap tenant di seeder. Bisa juga menambah akun demo Manajer/Kasir yang sudah terverifikasi agar QA per-role mudah.

### P2

**T-06 Kalender hari: kolom default dipilih alfabetis, bukan berdasarkan booking.**

> **Tindak lanjut A — selesai (19 Nov 2026).** Kolom default Kalender mendahulukan sesi berjalan, lalu layanan yang punya booking. Pilihan manual tetap dihormati. Commit `5a618b2`.
- Reproduksi: Kalender → Hari menampilkan "Layanan 6 dari 10". Sesi yang sedang berlangsung (Caca Marica, Green Screen 13.00) dan Bunga Citra (Podcast) tidak tampil, sementara layanan dengan slot kosong/lewat tampil.
- Penyebab: `ArrangeDayGrid.php:21-22` mengambil 6 layanan pertama yang punya slot.
- Saran: urutkan dulu layanan yang punya booking (terutama yang sedang berlangsung), lalu yang punya slot kosong.

**T-07 Saran "jam sepi" bisa menunjuk jam tanpa slot.**
- Laporan → Okupansi menulis "Paling sepi: Kam, sekitar pukul 12.00", sedangkan Promo menulis "paling sepi pada Min, sekitar pukul 10.00" (periode berbeda: bulan ini vs 30 hari).
- Penyebab: `GetReport.php:537-562` memilih hari dan jam secara terpisah dari jumlah booking absolut. Jam tidak dibatasi ke jam yang punya slot, dan seri (banyak nilai 0) dipecah secara acak.
- Saran: hitung per sel (hari × jam) dari okupansi slot yang benar-benar dibuka, abaikan sel tanpa slot, dan samakan periodenya.

**T-08 Laporan Okupansi mencampur satuan.**
- Angka utama "6,4% (60 dari 943 kursi)" berbasis kursi, strip "59 slot terisi / 86 slot kosong" berbasis slot (≈41%), dan "Pemakaian unit" sampai 78,8%. Tiga angka itu tampak bertentangan.
- Saran: beri label satuan yang jelas, atau pakai satu basis.

**T-09 Guardrail ADR-0012 gagal:** `app/Actions/Service/GetLayananTabCounts.php`. Satu komentar `scope-bypass` dipakai untuk tiga query; guardrail menghitung 2 query tanpa alasan. Perbaikan: komentar per query atau satu query per baris dengan komentar.

> **Tindak lanjut A — selesai (19 Nov 2026).** Setiap query bypass scope memiliki komentar alasan. Guardrail lulus. Commit `2d26195`.

**T-10 Perbandingan periode "Bulan ini" tidak setara.**
- "1 Nov–30 Nov dibanding 1 Okt–31 Okt": bulan berjalan (18 hari) dibandingkan dengan bulan penuh. Booking dihitung menurut tanggal sesi (termasuk yang akan datang), sedangkan pendapatan menurut tanggal bayar. Panah "naik/turun" (`GetReport::conclusion`, `ReportPeriod::previous()` baris 91) bisa menyesatkan.
- Saran: bandingkan dengan rentang hari yang sama (1–18 Okt), atau beri keterangan.

**T-11 Command terjadwal tanpa isolasi galat per tenant.**

> **Tindak lanjut A — selesai (19 Nov 2026).** Ketiga command melaporkan kegagalan per tenant dan melanjutkan tenant berikutnya. Ringkasan mingguan mensyaratkan paket efektif. Commit `de44328`.
- `routes/console.php:66, 93, 114`: satu exception di satu tenant menghentikan sisa tenant.
- `owners:weekly-summary` juga mengirim ke semua tenant, termasuk yang paketnya habis atau tidak aktif.
- Saran: `try/catch` + `report()` per tenant, dan filter tenant dengan paket efektif.

**T-12 Auto-complete langsung aktif untuk semua tenant lama.**

> **Tindak lanjut A — selesai (19 Nov 2026).** D-027 disetujui. Auto-complete berlaku untuk sesi yang berakhir sejak aktivasi atau aktivasi ulang. Sesi lama bisa ditutup manual. Migrasi baru sudah diterapkan lokal. Commit `de44328`.
- Migrasi `add_owner_automation_settings` men-default `auto_complete_bookings = true`. Run pertama setelah deploy menandai selesai **semua** booking `paid` yang sudah lewat (123 di data seed) tanpa pemberitahuan ke pemilik.
- Pastikan ini disengaja (D-026). Bila ya, catat di release notes. Bila tidak, batasi run pertama ke sesi N hari terakhir.

**T-13 Beban query di jalur yang sering dipanggil.**

> **Tindak lanjut A — selesai (19 Nov 2026).** Kursi terisi dan booking saat ini diagregasi. Hitungan sesi lewat memakai SQL. Pemrosesan memakai batch 200, dengan batas 500 kandidat per pengiriman manual. Commit `de44328`.
- `ListWalkInSlots.php:79`: `BookingRules::seatsTaken()` dipanggil per slot.
- `GetAvailableSchedules.php:269`: `$slot->bookings()->exists()` per slot.
- Keduanya terpakai oleh polling Beranda tiap 60 detik (`GetBeranda::live`, `empty_slots`) dan form Catat booking.
- `CompleteBookings::endedIds()` (baris 66) memuat semua booking `paid` lampau tanpa batas setiap kali tab "Perlu tindakan" dibuka (`OwnerBookingController.php:70`). `all_ended` juga tidak terkena batas `max:500`.
- Waktu lokal saat ini masih wajar (polling ±0,3 s, Slot ±0,9 s), tapi akan naik seiring data.
- Saran: eager-load/aggregate, `count()` di query, dan batasi jumlah.

**T-14 Email ringkasan mingguan masih berbranding "Laravel".**
- Isi memakai "Regards, Laravel", "© 2026 Laravel" dan footer bahasa Inggris "If you're having trouble clicking…".
- Penyebab: `APP_NAME=Laravel` di `.env`/`.env.example` dan template mail bawaan.
- Saran: set `APP_NAME=BookQu` dan publish/terjemahkan template notifikasi.

**T-15 Tanggal berbahasa Inggris bila `APP_LOCALE=en`.**

> **Bagian A (T-15a) — selesai (19 Nov 2026).** Tanggal Kalender dan toast pindah jadwal memakai locale Indonesia. Bagian Beranda, email, dan validasi pelanggan tetap dilacak sebagai bagian B. Commit `5a618b2, 9c1a93f`. Bagian B belum diverifikasi dalam sesi ini.
- Muncul di Beranda "Besok · Thursday, 19 Nov" dan Kalender "Wednesday, 18 Nov 2026" (`.env` lokal memakai `APP_LOCALE=en`).
- Penyebab: `translatedFormat()` tanpa `->locale('id')` di `GetBeranda.php:397`, `GetOwnerCalendarData.php:44-46,102` dan `SendWeeklySummary` (from/to/openUntil). Bagian lain sudah memakai `->locale('id')`.
- Sesi 2 menemukan tempat lain yang juga terdampak:
  - toast pindah jadwal "berhasil diubah ke Saturday, 28 November 2026";
  - pesan validasi pelanggan "The jam field is required." di halaman Pilih Jadwal.
- Saran: konsisten pakai `->locale('id')` (atau paksa locale owner portal), dan pastikan `APP_LOCALE=id` di semua lingkungan.

**T-16 Kartu promo tidak menampilkan batasan hari/jam.**
- Promo `SEPI20` (Sen–Sel, 10.00–12.00) tampil "Berlaku Tanpa batas waktu". Field `period` di `OwnerVoucherController.php:85` hanya membaca tanggal.
- Saran: tambahkan "Sen–Sel · 10.00–12.00" (bisa pakai `OwnerScheduleController::dayRange`).

**T-26 Jam di riwayat booking memakai UTC.** *(sudah ada sebelum BQ-162)*

> **Tindak lanjut A — selesai (19 Nov 2026).** Log baru memakai timestamp aplikasi dan ditampilkan dalam WIB. SQLite dan MySQL lokal diperiksa. Zona waktu produksi serta interpretasi histori lama perlu pemeriksaan rilis. Commit `029d0d7`.
- Reproduksi: pindah jadwal pukul ±19.57 WIB → riwayat slip menulis "18/11/2026 12:57".
- Penyebab: `BookingLog` memakai `$timestamps = false` dan `created_at` diisi default DB `CURRENT_TIMESTAMP`. Di SQLite nilainya UTC; di MySQL nilainya mengikuti `time_zone` server. Bila server produksi UTC, semua riwayat (aktor, waktu) mundur 7 jam.
- Saran: isi `created_at` dari aplikasi (`now()`), lalu cek `time_zone` MySQL produksi.

**T-27 Alasan default pindah jadwal menyebut "walk-in" untuk booking online.** *(sudah ada sebelum BQ-162)*

> **Tindak lanjut A — selesai (19 Nov 2026).** Alasan kosong ditampilkan sebagai tanda pisah. Riwayat pindah jadwal memakai nama dan peran aktor. Commit `9c1a93f`.
- Bila alasan dikosongkan, riwayat menulis "Jadwal diubah oleh Owner (Walk-in / On-site): Permintaan langsung customer walk-in di studio". Ini muncul juga untuk booking online, dengan istilah campur Inggris.
- Sumber: `OwnerBookingController.php:415` dan `RescheduleBooking.php:145`.
- Saran: gunakan "Jadwal diubah oleh {nama} ({peran})" dan alasan kosong ditulis "—".

### P3

- **T-17** Overflow horizontal di 768 px:

> **Bagian A (T-17a) — selesai (19 Nov 2026).** Layout jam mingguan memakai container query agar baris mengikuti lebar panel, termasuk pada 768 px. Cek browser ulang masih diperlukan. Commit `01da216`. Bagian B belum diverifikasi dalam sesi ini.
  - Jam Buka & Harga (tab Jam buka): input jam melebar +46 px.
  - Laporan → Pendapatan: angka "Rp19.900.000" +56 px.
- **T-18** Peringatan runway bertentangan: banner "Audio Voice Over …: slot hanya sampai 2 Des", padahal angka di bawahnya "1 layanan kurang dari 7 hari" dan Beranda menulis "belum punya slot terbuka untuk 7 hari ke depan". Pesan perlu menyebut celah 7 hari, bukan tanggal terakhir.

> **Tindak lanjut A — selesai (19 Nov 2026).** Banner menjelaskan cakupan tujuh hari, sehingga tanggal slot terjauh tidak lagi dijadikan kesimpulan cakupan. Commit `01da216`.
- **T-19** Tombol bulk "Tandai selesai ( 1 )" ada spasi di dalam kurung (`bookings.blade.php:109`, whitespace di sekitar `<span>`). Tombol konfirmasinya juga berwarna merah (gaya danger) untuk aksi yang tidak destruktif.

> **Tindak lanjut A — selesai (19 Nov 2026).** Label jumlah terpilih dirender tanpa spasi tambahan. Konfirmasi selesai memakai warna primary. Commit `9c1a93f`.
- **T-20** Format jam tidak konsisten di Beranda: rail "08.00", baris "08:00 – 09:00", Besok "14:00". Aturan prototipe memakai titik.
- **T-21** Statistik Promo "Rp0 / 0 kali dipakai" bertentangan dengan kartu "14/100 dipakai". Kemungkinan `used_count` di seed tidak punya booking nyata; cek `GetPromoFigures` dengan data asli.
- **T-22** Gambar rusak di Halaman depan: `/storage/assets/banner-promo.jpg` (asset seed tanpa file/`storage:link`).
- **T-23** Halaman Unit: 5 tombol tanpa nama aksesibel (ikon tanpa `aria-label`).

> **Tindak lanjut A — selesai (19 Nov 2026).** Pilihan gambar pada gallery-picker yang dipakai Unit diberi nama aksesibel, termasuk ketika alt kosong. Commit `6b88db9`.
- **T-24** Form Promo masih gaya lama (label Title Case "Kode Promo", "Tipe Diskon", placeholder huruf besar), belum mengikuti copy prototipe.
- **T-25** PHPStan `OwnerScheduleController.php:75` (lihat bagian 2).

> **Tindak lanjut A — selesai (19 Nov 2026).** Pembacaan nama layanan diperbaiki. PHPStan lulus. Commit `2d26195`.
- **T-28** Tab Booking "Mendatang" default "Sesi terjauh". Tanggal 2 Des tampil paling atas, "Hari ini" paling bawah, dan jam dalam satu hari menurun. Untuk tab ini sebaiknya default "Sesi terdekat".

> **Tindak lanjut A — selesai (19 Nov 2026).** Tab Mendatang default ke sesi terdekat. Pilihan urutan eksplisit tetap dipakai. Commit `9c1a93f`.
- **T-29** Format rupiah tidak konsisten:

> **Bagian A (T-29a) — selesai (19 Nov 2026).** Daftar Booking, slip, dan harga dialog pindah jadwal memakai formatter Rupiah. Commit `9c1a93f`. Bagian B belum diverifikasi dalam sesi ini.
  - "Rp 200.000" (kolom Dibayar, slip, dialog pencairan langkah 3; `OwnerBookingController` memakai `number_format` dengan spasi);
  - "Rp200.000" (`App\Support\Rupiah`, dipakai Beranda/Pelanggan/Saldo).
- **T-30** Slip booking memakai format lain: tanggal "02/12/2026" dan jam "09:00 - 10:00". Halaman lain memakai "Rabu, 2 Des" dan "09.00 – 10.00".

> **Tindak lanjut A — selesai (19 Nov 2026).** Tanggal sesi slip memakai nama hari dan bulan Indonesia. Jam memakai titik dan rentang en dash. Commit `9c1a93f`.
- **T-31** Dialog pindah jadwal menampilkan jam akhir mentah "09:00 - 09:59". `BulkCreateSchedules` (baris 201) menyimpan akhir slot dengan `subMinute()`. Tampilan lain sudah merapikannya lewat `BookingTimeRange`. Fitur buka slot otomatis (C-01) membuat slot seperti ini dalam jumlah besar.

> **Tindak lanjut A — selesai (19 Nov 2026).** Label jam dialog pindah jadwal berasal dari BookingTimeRange. Akhir inklusif 09:59 ditampilkan sebagai 10.00. Commit `9c1a93f`.
- **T-32** Dialog pencairan:
  - bila jumlah melebihi saldo, tombol "Lanjut" hanya nonaktif tanpa pesan alasan;
  - copy campur Inggris: "Masih ada pengajuan withdraw…", status "Pending".
- **T-33** Pembayaran → Saldo: "Saldo tersedia Rp18.500.000" tetapi "Saldo bersih dari riwayat mutasi Rp0 / Belum ada mutasi". Kemungkinan seed tidak membuat baris mutasi; cek dengan data nyata. Refund "Menunggu transfer" Rp200.000 juga tidak terlihat memengaruhi saldo, jadi perlu dikonfirmasi apakah memang begitu desainnya.
- **T-34** Permintaan pembatalan dari Kasir tampil dua kali di "Perlu tindakan": kartu di atas filter dan baris tabel ("Tinjau").

> **Tindak lanjut A — selesai (19 Nov 2026).** Permintaan pembatalan pada Perlu tindakan ditinjau dari baris terkait. Aksi keputusan tetap tersedia. Commit `9c1a93f`.
- **T-35** Pelanggan yang memakai kode promo jam sepi di luar jam berlakunya hanya mendapat "Kode promo ini hanya berlaku pada hari dan jam tertentu." tanpa menyebut harinya/jamnya.
- **T-36** Simpan Unit dengan `service_ids` yang sudah dihapus atau milik tenant lain menghasilkan 404 polos, bukan pesan validasi. Keamanannya benar (ditolak), tapi UX-nya membingungkan bila layanan baru dihapus di tab lain.

> **Tindak lanjut A — selesai (19 Nov 2026).** ID layanan asing atau tidak tersedia menghasilkan validasi service_ids. Relasi tetap terlindungi. Commit `6b88db9`.
- **T-37** Aksesibilitas (cek otomatis):
  - input form Promo (`code`, `discount_type`, `discount_value`, …) tidak terhubung ke `<label>`;
  - setiap halaman owner punya ±26 elemen `aria-live`/`role=status`, berpotensi berisik untuk screen reader.
- **T-38** Ada dua angka "menunggu bayar":
  - Beranda "Menunggu pembayaran 6" (hanya booking dengan tahanan slot aktif);
  - tab Booking "Menunggu bayar 27" (semua status `pending`).

  Angkanya berbeda selama `bookings:expire-payments` belum jalan. Samakan definisinya atau labelnya.
- **T-39** Beranda Kasir menampilkan banner "4 layanan belum punya slot terbuka…", padahal Kasir tidak punya akses ke Jam Buka & Harga.
- **T-40** Data seed tidak konsisten: 21 Nov sudah tercatat sebagai tanggal tutup ("Maintenance Peralatan"), padahal ada 6 booking aktif di tanggal itu. Bersama T-05, perbaiki seeder agar QA tidak tersesat.

## 4. Yang sudah diuji dan berfungsi

- Catat booking:
  - pilih layanan/slot;
  - sisa kursi tampil benar;
  - saran pelanggan lama muncul setelah 2 huruf dan mengisi HP/email;
  - simpan berhasil dan Beranda ter-update;
  - slot penuh mengembalikan 409 tanpa membuat booking.
- Booking → Perlu tindakan: tandai selesai terpilih dan "Tandai selesai semua" (konfirmasi + toast, hitungan tab ter-update).
- Promo jam sepi: buat, ubah (nilai jam terisi `HH:MM`), dan hapus batasan (DB kembali `null`).
- Pengaturan → Kebijakan: switch "Tandai selesai otomatis" tersimpan. Notifikasi: preferensi "Ringkasan mingguan" tampil untuk Pemilik.
- Laporan, Pelanggan, Kalender dan Jam Buka & Harga tampil tanpa error console. Ekspor Laporan, QR, poster, runway, pencarian global dan slot walk-in merespons 200.

Ditambahkan di sesi 2:

- **Akses per role:**
  - Manajer ditolak (403) hanya di Pembayaran, Tim, dan Paket & Tagihan.
  - Kasir hanya bisa membuka Beranda, Kalender, Booking, Pelanggan, Notifikasi, Bantuan, dan Pengaturan (tab Notifikasi + Akun).
  - Panggilan langsung Kasir ke endpoint terlarang semuanya 403: status booking, keputusan pembatalan, kebijakan, promo, jam buka, pencairan, rekening, profil, ekspor. Kasir boleh "Tandai selesai semua" (sesuai D-026).
  - Preferensi "Ringkasan mingguan" tersembunyi untuk Kasir.
- **Slip booking:**
  - pindah jadwal menampilkan selisih harga akhir pekan (+Rp20.000) dan mencatatnya di riwayat;
  - Pemilik membatalkan booking online → refund penuh "Menunggu transfer";
  - Kasir mengajukan pembatalan → muncul di Perlu tindakan → Pemilik menyetujui → booking `cancelled` + refund dibuat.
- **Pembayaran:** simpan rekening (wajib password) lalu pencairan 3 langkah. Saldo tertahan, pengajuan kedua diblokir saat masih ada yang diproses.
- **CRUD:**
  - Layanan: buat, ubah, nonaktifkan, hapus.
  - Kategori dan Tambahan: buat.
  - Unit: buat. ID layanan tenant lain ditolak.
  - Balas ulasan berhasil.
- **Libur & tutup:** tanggal dengan 6 booking aktif ditolak dan daftar booking yang bentrok ditampilkan.
- **Checkout pelanggan:** kode `SEPI20` (Sen–Sel 10.00–12.00) memberi diskon Rp30.000 di slot Senin 10.00 dan ditolak di slot Senin 14.00.
- **Tampilan:** 1024 px bersih untuk semua role. Cek aksesibilitas statis di 15 halaman: setiap halaman punya satu `h1`, tidak ada gambar tanpa `alt`, dan semua tombol punya nama (kecuali T-23 dan T-37).

## 5. Sisa pekerjaan

Pengujian sesi 1–2 selesai pada 18 Nov 2026. Per 19 Nov 2026, implementasi Developer A dan keputusan T-12 sudah selesai. Verifikasi lanjutan berikut masih perlu dijadwalkan:

1. **Pass screen reader manual (NVDA/VoiceOver)** pada shell, Booking, slip, dan Catat booking. Ini butuh orang di depan mesin; cek otomatis di atas bukan penggantinya. Item ini juga masih terbuka di dokumen BQ-162 milik pembuat branch.
2. **T-12 sudah diputuskan dan diimplementasikan melalui D-027.** Status T-33 (saldo vs refund) perlu diperbarui berdasarkan keputusan dan bukti dari Developer B.
3. **Crawl ulang setelah perbaikan.** Skrip dan hasil crawler yang disebut pada laporan awal belum ditemukan di checkout repo ini. Lokasi yang tercatat pada pengujian awal adalah `docs/work/BQ-162/crawl-owner-pages.mjs` (hasil: `crawl-hasil-2026-11-18.json` untuk Pemilik, `crawl-hasil-roles-2026-11-18.json` untuk Manajer/Kasir). Jalankan saat app aktif di `localhost:8020`: `ROLES=pemilik,manajer,kasir WIDTHS=375,768,1024,1366 TEMP_PROFILE=<folder profil Edge> node --experimental-websocket crawl-owner-pages.mjs out.json`.
