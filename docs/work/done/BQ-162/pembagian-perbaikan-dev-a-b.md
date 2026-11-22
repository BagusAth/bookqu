# Pembagian perbaikan BQ-162 untuk Developer A dan Developer B

Sumber: hasil pengujian branch `agathan-bq-162-papan-jadwal` @ `22e1318` (18 Nov 2026). Detail tiap temuan (langkah reproduksi, file:baris, penyebab) ada di `temuan-pengujian-2026-11-18.md` di folder yang sama. Dokumen ini memuat pembagian awal dan status hasil implementasi. Detail historis memakai keadaan commit sumber.

## Status tindak lanjut per 19 November 2026

Implementasi **21 bagian Developer A selesai** pada branch `agathan-bq-162-papan-jadwal`, sampai commit `379dfd7`. Status Developer B belum diverifikasi dalam sesi ini. T-15, T-17, dan T-29 memiliki bagian A dan B yang dilacak terpisah.

Bukti terbaru tercatat pada [BQ-163](../BQ-163-owner-automation-and-booking-follow-up.md) dan [BQ-162](../BQ-162-papan-jadwal.md). Suite penuh SQLite lulus 1.020 tes / 6.695 assertion. Suite MySQL terkait lulus 35 tes / 147 assertion. Regresi batas batch setelahnya lulus di kedua database. PHPStan, Pint pada file berubah, dan pemeriksaan integritas dokumentasi lulus. Build frontend lulus pada tahap perbaikan UI.

Crawl browser setelah perbaikan, pemeriksaan NVDA/VoiceOver, pemeriksaan zona waktu produksi, serta verifikasi setelah pekerjaan B digabung masih perlu dijalankan saat verifikasi rilis. Status selesai di sini merujuk pada implementasi dan bukti otomatis yang disebutkan.

| ID | Status | Hasil implementasi | Commit |
|---|---|---|---|
| T-01 | Selesai | Pilihan per layanan dipertahankan saat simpan jam buka. Pilihan massal harus dipilih secara eksplisit. | `01da216` |
| T-02 | Selesai | Ekspor tanpa tab memakai Semua, termasuk nilai tab kosong. Halaman daftar tetap membuka Perlu tindakan. | `2d26195, 9c1a93f` |
| T-06 | Selesai | Kolom default Kalender mendahulukan sesi berjalan, lalu layanan yang punya booking. Pilihan manual tetap dihormati. | `5a618b2` |
| T-09 | Selesai | Setiap query bypass scope memiliki komentar alasan. Guardrail lulus. | `2d26195` |
| T-11 | Selesai | Ketiga command melaporkan kegagalan per tenant dan melanjutkan tenant berikutnya. Ringkasan mingguan mensyaratkan paket efektif. | `de44328` |
| T-12 | Selesai | D-027 disetujui. Auto-complete berlaku untuk sesi yang berakhir sejak aktivasi atau aktivasi ulang. Sesi lama bisa ditutup manual. Migrasi baru sudah diterapkan lokal. | `de44328` |
| T-13 | Selesai | Kursi terisi dan booking saat ini diagregasi. Hitungan sesi lewat memakai SQL. Pemrosesan memakai batch 200, dengan batas 500 kandidat per pengiriman manual. | `de44328` |
| T-15a | Selesai | Tanggal Kalender dan toast pindah jadwal memakai locale Indonesia. Bagian Beranda, email, dan validasi pelanggan tetap dilacak sebagai bagian B. | `5a618b2, 9c1a93f` |
| T-17a | Selesai | Layout jam mingguan memakai container query agar baris mengikuti lebar panel, termasuk pada 768 px. Cek browser ulang masih diperlukan. | `01da216` |
| T-18 | Selesai | Banner menjelaskan cakupan tujuh hari, sehingga tanggal slot terjauh tidak lagi dijadikan kesimpulan cakupan. | `01da216` |
| T-19 | Selesai | Label jumlah terpilih dirender tanpa spasi tambahan. Konfirmasi selesai memakai warna primary. | `9c1a93f` |
| T-23 | Selesai | Pilihan gambar pada gallery-picker yang dipakai Unit diberi nama aksesibel, termasuk ketika alt kosong. | `6b88db9` |
| T-25 | Selesai | Pembacaan nama layanan diperbaiki. PHPStan lulus. | `2d26195` |
| T-26 | Selesai | Log baru memakai timestamp aplikasi dan ditampilkan dalam WIB. SQLite dan MySQL lokal diperiksa. Zona waktu produksi serta interpretasi histori lama perlu pemeriksaan rilis. | `029d0d7` |
| T-27 | Selesai | Alasan kosong ditampilkan sebagai tanda pisah. Riwayat pindah jadwal memakai nama dan peran aktor. | `9c1a93f` |
| T-28 | Selesai | Tab Mendatang default ke sesi terdekat. Pilihan urutan eksplisit tetap dipakai. | `9c1a93f` |
| T-29a | Selesai | Daftar Booking, slip, dan harga dialog pindah jadwal memakai formatter Rupiah. | `9c1a93f` |
| T-30 | Selesai | Tanggal sesi slip memakai nama hari dan bulan Indonesia. Jam memakai titik dan rentang en dash. | `9c1a93f` |
| T-31 | Selesai | Label jam dialog pindah jadwal berasal dari BookingTimeRange. Akhir inklusif 09:59 ditampilkan sebagai 10.00. | `9c1a93f` |
| T-34 | Selesai | Permintaan pembatalan pada Perlu tindakan ditinjau dari baris terkait. Aksi keputusan tetap tersedia. | `9c1a93f` |
| T-36 | Selesai | ID layanan asing atau tidak tersedia menghasilkan validasi service_ids. Relasi tetap terlindungi. | `6b88db9` |

Laporan reproduksi awal tersedia di [temuan pengujian](temuan-pengujian-2026-11-18.md).

## 1. Prinsip pembagian

- **Dibagi per area file** supaya dua developer tidak mengedit file yang sama:
  - **Developer A:** Booking, Jadwal (Jam Buka & Harga, Slot), Kalender, Unit, command terjadwal.
  - **Developer B:** Beranda, Pelanggan, Laporan, Promo, Pembayaran, email/locale, seeder.
- Setiap developer punya P1 sendiri, jadi keduanya bisa mulai bersamaan.
- **Lane** mengikuti `AGENTS.md`. Temuan yang menyentuh aturan domain, skema/data, atau keamanan masuk **heavy** dan butuh persetujuan Tech Lead sebelum coding. Sisanya **light**.
- Prioritas:
  - **P1** = salah data/perilaku, wajib sebelum merge ke `integration`;
  - **P2** = mengganggu pengguna/berisiko;
  - **P3** = kosmetik/kualitas.

## 2. Ringkasan beban

| | Developer A | Developer B |
|---|---|---|
| P1 | T-01, T-02 | T-03, T-04, T-05 |
| P2 | T-06, T-09, T-11, T-13, T-26, T-27 | T-07, T-08, T-10, T-14, T-15, T-16 |
| P3 | T-17a, T-18, T-19, T-23, T-25, T-28, T-29a, T-30, T-31, T-34, T-36 | T-17b, T-20, T-21, T-22, T-24, T-29b, T-32, T-35, T-37, T-38, T-39, T-40 |
| Keputusan awal | T-12 selesai melalui D-027 | T-33 belum diverifikasi |
| Jumlah bagian | 21 (termasuk T-15a) | 22 |

Test yang gagal pada pengujian awal (4, sama di SQLite dan MySQL; kini lulus pada suite SQLite terbaru) semuanya milik **Developer A**:

| Test | Diperbaiki oleh |
|---|---|
| `CoreFlowIntegrationTest::test_owner_only_sees_own_tenant_bookings` | T-02 |
| `OwnerUiAuditTest::test_owner_pages_render_when_optional_service_or_account_relations_are_missing` | T-02 |
| `OwnerPortalWorkflowTest::test_csv_export_is_plan_gated_and_neutralizes_formula_cells` | T-02 |
| `ArchitectureRulesTest::test_scope_bypasses_carry_a_reason_comment` | T-09 |

## 3. Developer A — Booking, Jadwal, Kalender, Unit, Scheduler

### P1 (kerjakan dulu)

| ID | Masalah | Yang diperbaiki | File utama | Lane | Selesai bila |
|---|---|---|---|---|---|
| T-01 — selesai | Simpan "Jam buka" menyalakan/mematikan buka slot otomatis untuk **semua** layanan | Kirim `auto_open_slots` hanya bila switch benar-benar diubah, atau tampilkan status "Sebagian" tanpa menimpa | `public/js/owner-schedule.js:62`, `resources/views/owner/schedule/hours.blade.php:7`, `app/Actions/Schedule/SaveOpeningHours.php:54` | Heavy (aturan jadwal, FR-SCHEDULE-015) | Test baru: 1 dari N layanan aktif, simpan jam buka tanpa ubah switch, tetap 1 dari N |
| T-02 — selesai | Ekspor CSV tanpa `tab` hanya berisi "Perlu tindakan"; 3 test gagal | Ekspor default ke `all` bila `tab` kosong; daftar Booking tetap default "Perlu tindakan". Perbarui assertion 3 test ke perilaku baru dan jelaskan alasannya di pesan commit | `app/Http/Controllers/Owner/OwnerBookingController.php:144-195` + 3 test di atas | Light | `/owner/bookings/export` berisi semua booking; 3 test hijau |

### P2

| ID | Masalah | Yang diperbaiki | File utama | Lane |
|---|---|---|---|---|
| T-09 — selesai | Guardrail ADR-0012 gagal | Satu komentar `// scope-bypass: …` untuk setiap query | `app/Actions/Service/GetLayananTabCounts.php` | Light |
| T-06 — selesai | Kalender hari menyembunyikan sesi yang sedang berlangsung (6 kolom dipilih alfabetis) | Urutkan dulu layanan yang punya booking hari itu (yang sedang berlangsung paling depan), baru yang hanya punya slot | `app/Actions/Calendar/ArrangeDayGrid.php:21-22` | Light |
| T-11 — selesai | Satu tenant yang error menghentikan command untuk semua tenant; ringkasan mingguan juga terkirim ke tenant yang paketnya habis | `try/catch` + `report()` per tenant di tiga command; filter tenant dengan paket efektif | `routes/console.php:66, 93, 114` | Heavy (ops) |
| T-13 — selesai | N+1 di polling Beranda/Catat booking; `endedIds()` tanpa batas | Aggregate `seatsTaken`/`exists` per slot dalam satu query; hitung "sesi lewat" dengan `count()`; batasi `all_ended` | `ListWalkInSlots.php:79`, `GetAvailableSchedules.php:269`, `CompleteBookings.php:66`, `OwnerBookingController.php:70` | Heavy (aturan okupansi lewat `BookingRules`) |
| T-26 — selesai | Jam riwayat booking dalam UTC (mundur 7 jam) *(sudah ada sebelum BQ-162)* | Isi `created_at` dari aplikasi; cek `time_zone` MySQL produksi | `app/Models/BookingLog.php` + pemanggil `BookingLog::create` | Heavy (data) |
| T-27 — selesai | Alasan default pindah jadwal menyebut "walk-in" untuk booking online *(sudah ada sebelum BQ-162)* | Teks riwayat: "Jadwal diubah oleh {nama} ({peran})", alasan kosong = "—" | `OwnerBookingController.php:415`, `app/Actions/Booking/RescheduleBooking.php:145` | Light |

### P3

| ID | Masalah | File utama |
|---|---|---|
| T-17a — selesai | Overflow 768 px di Jam Buka & Harga → Jam buka (input jam +46 px) | `resources/views/owner/schedule/hours.blade.php` |
| T-18 — selesai | Banner runway "slot hanya sampai 2 Des" bertentangan dengan "kurang dari 7 hari" | `GetScheduleOverview.php`, `resources/views/owner/schedule.blade.php` |
| T-19 — selesai | "Tandai selesai ( 1 )" ada spasi; tombol konfirmasi berwarna danger | `resources/views/owner/bookings.blade.php:109` |
| T-23 — selesai | 5 tombol ikon tanpa nama aksesibel di Unit | `resources/views/owner/units.blade.php` |
| T-25 — selesai | PHPStan `namalayanan` pada `Model` generik | `OwnerScheduleController.php:75` |
| T-28 — selesai | Tab "Mendatang" default "Sesi terjauh" | `OwnerBookingController.php` (urutan per tab) |
| T-29a — selesai | "Rp 200.000" (spasi) di daftar Booking & slip; pakai `App\Support\Rupiah` | `OwnerBookingController.php`, `GetOwnerBookingPanel.php` |
| T-30 — selesai | Slip memakai "02/12/2026" dan "09:00 - 10:00" | `GetOwnerBookingPanel.php`, partial slip |
| T-31 — selesai | Dialog pindah jadwal menampilkan jam akhir "09:59" | Pakai `BookingTimeRange` di `getAvailableSlots` / view dialog |
| T-34 — selesai | Permintaan pembatalan tampil dua kali di "Perlu tindakan" | `resources/views/owner/bookings.blade.php` |
| T-36 — selesai | Unit dengan `service_ids` asing/terhapus → 404 polos | `app/Actions/Unit/SaveUnit.php` (ganti dengan `ValidationException`) |

**Selesai — T-15a**, bagian T-15 milik A (karena filenya milik A): `->locale('id')` di `GetOwnerCalendarData.php:44-46,102` dan di toast pindah jadwal (`OwnerBookingController::reschedule`).

### Keputusan T-12 — selesai

D-027 disetujui melalui konfirmasi developer pada 19 Nov 2026. Implementasi menggunakan penanda `tenants.auto_completion_started_at`; migrasi baru mengisi awal aktivasi bagi tenant yang sudah aktif. Sesi lama tetap dapat ditutup manual. Detail ada pada FR-BOOKING-024 dan BQ-163.

## 4. Developer B — Beranda, Pelanggan, Laporan, Promo, Pembayaran, Email, Seeder

### P1 (kerjakan dulu, mulai dari T-05)

| ID | Masalah | Yang diperbaiki | File utama | Lane | Selesai bila |
|---|---|---|---|---|---|
| T-05 | Seeder tidak membuat `tenant_members` → pemilik seed dapat 403 | Panggil `CreateOwnerMembership` untuk tiap tenant; tambah akun demo Manajer & Kasir yang sudah terverifikasi | `database/seeders/DatabaseSeeder.php`, `BramaStudioSeeder.php` | Light | `migrate:fresh --seed` → login `brama@bookqu.test` membuka Beranda (200) |
| T-03 | "Terakhir datang" menampilkan tanggal masa depan; segmen "Lama tidak datang" ikut salah | Hitung `MAX(tanggalbooking)` hanya dari sesi yang sudah lewat dengan status `paid`/`completed` | `app/Actions/Customer/ListCustomers.php:158` | Light | Test: pelanggan dengan booking besok + booking 70 hari lalu → "Terakhir datang" = 70 hari lalu, masuk "Lama tidak datang" |
| T-04 | Beranda tenant lama hanya menampilkan panduan; tampilan harian hilang | Backfill `owner_guide_completed_at` untuk tenant yang sudah punya booking; tombol "Lewati panduan"; tandai langkah 4 walau clipboard gagal | Migrasi baru, `GetBeranda.php:216-243`, `public/js/owner-ui.js:353` | Heavy (migrasi data) | Tenant dengan booking langsung melihat Perlu Anda + Jadwal hari ini |

### P2

| ID | Masalah | Yang diperbaiki | File utama | Lane |
|---|---|---|---|---|
| T-07 | Saran "jam sepi" bisa menunjuk jam tanpa slot; Laporan dan Promo memberi saran berbeda | Hitung per sel hari × jam dari okupansi slot yang dibuka; abaikan sel tanpa slot; samakan periodenya | `app/Actions/Report/GetReport.php:537-562`, `OwnerVoucherController::index` | Light |
| T-08 | Okupansi mencampur kursi, slot, dan unit | Satu basis, atau label satuan yang jelas | `GetReport.php`, `resources/views/owner/analytics.blade.php` | Light |
| T-10 | "Bulan ini" dibanding bulan lalu penuh | Bandingkan rentang hari yang sama, atau beri keterangan | `app/Support/ReportPeriod.php:91`, `GetReport::conclusion` | Light |
| T-14 | Email ringkasan berbranding "Laravel", footer Inggris | `APP_NAME=BookQu` di `.env.example`; publish + terjemahkan template mail | `.env.example`, `resources/views/vendor/notifications` | Light |
| T-15 | Tanggal/teks Inggris bila `APP_LOCALE=en` (bagian B) | `->locale('id')` di Beranda "Besok" dan `SendWeeklySummary`; pastikan `lang/id/validation.php` dipakai di halaman pelanggan | `GetBeranda.php:397`, `SendWeeklySummary.php`, `lang/id` | Light |
| T-16 | Kartu promo tidak menampilkan batasan hari/jam | Tambahkan "Sen–Sel · 10.00–12.00" | `OwnerVoucherController.php:85`, `resources/views/owner/vouchers.blade.php` | Light |

### P3

| ID | Masalah | File utama |
|---|---|---|
| T-17b | Overflow 768 px di Laporan → Pendapatan ("Rp19.900.000" +56 px) | `resources/views/owner/analytics.blade.php` |
| T-20 | Format jam campur di Beranda ("08.00" vs "08:00") | `GetBeranda.php`, `resources/views/owner/dashboard.blade.php` |
| T-21 | Statistik Promo "0 kali dipakai" vs kartu "14/100" | `GetPromoFigures` (cek dengan data nyata) |
| T-22 | Gambar rusak `banner-promo.jpg` di Halaman depan | Seeder asset / `storage:link` |
| T-24 | Form Promo masih gaya lama (Title Case, placeholder kapital) | `resources/views/owner/vouchers.blade.php`, `partials/promo-limits.blade.php` |
| T-29b | "Rp 50.000" (spasi) di dialog pencairan langkah 3 | View/JS dialog pencairan |
| T-32 | Pencairan: "Lanjut" nonaktif tanpa alasan; teks "withdraw", "Pending" | `public/js/owner-ui.js` (payout), view Saldo, label status payout |
| T-35 | Penolakan promo jam sepi ke pelanggan tidak menyebut harinya/jamnya | `app/Domain/Booking/BookingPricing.php` (pesan validasi) |
| T-37 | Input form Promo tanpa `<label>`; ±26 live region per halaman | `vouchers.blade.php`, `partials/promo-limits.blade.php`, layout owner |
| T-38 | Dua angka "menunggu bayar" (Beranda 6 vs tab 27) | `GetBeranda::figures` (samakan definisi atau label) |
| T-39 | Banner slot tampil untuk Kasir tanpa aksi | `GetBeranda.php` (banner berdasarkan role) |
| T-40 | Seed: tanggal tutup 21 Nov padahal ada 6 booking aktif | `BramaStudioSeeder.php` |

### Menunggu keputusan

- **T-33** (Tech Lead): saldo tersedia tidak cocok dengan riwayat mutasi, dan refund "Menunggu transfer" tidak mengurangi saldo. Pastikan dulu desainnya, baru perbaiki seed atau ledger.

## 5. Urutan kerja awal dan penutupan

Seluruh bagian A pada urutan awal di bawah sudah diimplementasikan. T-12 telah diputuskan melalui D-027. Status bagian B dan verifikasi gabungan diperbarui setelah ada bukti dari pengerjaan B.

1. **Hari 1:**
   - B: T-05 (seeder) lalu push, supaya QA lokal dua developer berjalan.
   - A: T-09 + T-02, supaya suite kembali hijau.
2. **Hari 1–2:**
   - A: T-01 (minta persetujuan Tech Lead dulu).
   - B: T-03, lalu T-04 (minta persetujuan Tech Lead untuk migrasi backfill).
3. Setelah P1 selesai: P2 masing-masing, lalu P3.
4. T-12 dan T-33 dikerjakan setelah keputusannya ada.
5. Penutup (salah satu developer, setelah semua merge):
   - full suite SQLite + `phpunit.mysql.xml`, PHPStan, Pint, `npm run build`;
   - crawl ulang (`crawl-owner-pages.mjs`, 3 role × 4 lebar);
   - pass screen reader manual (NVDA/VoiceOver) pada shell, Booking, slip, dan Catat booking.

## 6. File yang dibagi (hindari konflik)

| File | Pemilik | Catatan |
|---|---|---|
| `OwnerBookingController.php` | A | B tidak mengubah |
| `GetBeranda.php` | B | A tidak mengubah |
| `GetReport.php`, `OwnerVoucherController.php` | B | |
| `routes/console.php` | A | B mengubah `SendWeeklySummary.php` saja |
| `public/js/owner-ui.js` | B (link booking, pencairan) | A cukup memakai `owner-schedule.js` |
| `App\Support\Rupiah` | Sudah ada; keduanya hanya memakai | Jangan buat formatter baru |

Setiap PR mengikuti `docs/workflow.md`: sync dari `integration`, satu commit per temuan (sebut ID T-xx di pesan commit), test terkait lalu full suite sekali.
