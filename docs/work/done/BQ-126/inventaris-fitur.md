# Inventaris fitur portal owner saat ini (BQ-126 Fase 2)

Lampiran BQ-126. Dokumen ini memetakan setiap route, field, dan aksi portal owner yang ada sekarang ke tempatnya di rancangan ulang (Rancangan §6, `docs/work/BQ-126/rancangan-ulang-portal-owner.md`). Tujuannya supaya tidak ada fitur yang hilang diam-diam saat `docs/owner-portal.md` ditulis ulang di Fase 3.

- **Dasar:** kode di `integration`, dicek 4 Nov 2026 (commit `2849848`).
- **Sumber yang dibaca:**
  - `php artisan route:list --path=owner`: 93 route;
  - Form Request di `app/Http/Requests/**`, dan validasi inline di `app/Http/Controllers/Owner/**`;
  - field dan tombol di `resources/views/owner/**` dan `resources/views/components/owner/**`;
  - kolom tabel dari `database/migrations`;
  - status modul dari `config/modules.php`.
- **Yang tidak dibaca:** data produksi.

**Arti kolom "Tujuan":**
- halaman atau tab baru di Rancangan §6;
- **YATIM:** belum punya tempat di Rancangan dan harus diputuskan di Fase 3;
- **Hapus:** dihapus karena sebuah keputusan.

**Arti status:**
- **Jalan:** berfungsi;
- **Palsu:** tampil tetapi tidak menyimpan atau tidak melakukan apa-apa;
- **Tersembunyi** atau **Terkunci:** status modul di `config/modules.php`. Tersembunyi berarti semua route menjawab 404. Terkunci berarti hanya GET yang jalan.

---

## 1. Ringkasan

| Halaman lama | Route | Modul / paket | Tujuan utama |
|---|---|---|---|
| Dashboard | 2 | — | Beranda |
| Calendar | 1 | — | Kalender |
| Bookings | 5 | — | Booking + panel detail booking |
| Schedule | 6 | — | Jam Buka & Harga |
| Services / Programs | 10 | — | Layanan → tab Layanan |
| Categories | 5 | — | Layanan → tab Kategori |
| Additional Items | 5 | `additional-items` tersembunyi | Layanan → tab Add-on |
| Staff & Resources | 9 | `staff-resources` tersembunyi | Staff → Pengaturan → Tim (artinya berubah); Resources → Unit |
| Customers | 3 | — | Pelanggan |
| Vouchers | 5 | `vouchers` tersembunyi | Promo |
| Reviews | 3 | — | Ulasan |
| Analytics | 2 | `analytics` terkunci, Medium | Laporan |
| Schedule Report | 2 | `schedule-report` terkunci | Laporan → tab Okupansi |
| Settings (Business, Account) | 6 | — | Pengaturan → Profil Bisnis, Akun |
| Appearance | 2 | `appearance` tersembunyi | Halaman Booking → Tampilan |
| Assets | 3 | `assets` tersembunyi | Halaman Booking → Galeri media |
| Landing Page | 2 | `landing-page` tersembunyi, Pro | Halaman Booking → Landing page |
| Payment settings | 3 | `direct-payment` tersembunyi | Sebagian ke Pembayaran; gateway milik bisnis dihapus (D-016) |
| Saldo & withdraw | 7 | `balance` dirilis | Pembayaran → Saldo & Pencairan, Rekening |
| Integrations | 1 | `integrations` terkunci | Pengaturan → Integrasi (D-019) |
| Subscription + checkout | 7 | — | Paket & Tagihan |
| Notifications | 4 | — | Lonceng di bilah atas |
| **Jumlah** | **93** | | |

---

## 2. Rincian per halaman

Setiap route owner muncul tepat satu kali di bagian ini.

### 2.1 Dashboard → Beranda

| Route | Aksi | Status | Tujuan |
|---|---|---|---|
| `owner.dashboard` | Sambutan, kartu angka (antara lain total pelanggan), grafik *Revenue Growth* dan *Daily Trends* (mingguan/bulanan), *Recent Bookings*, *Upcoming Schedule*, modal lengkapi profil, banner trial | Jalan | Beranda: angka hari ini, Perlu tindakan, jadwal hari ini. Grafik pindah ke Laporan |
| `owner.dashboard.polling` | Penyegaran data dashboard secara berkala | Jalan (BQ-089 mencatat XSS di jalur ini) | Beranda: penyegaran jadwal hari ini dan Perlu tindakan |

Komponen yang ikut pindah:
- **Modal lengkapi profil** (`modal-complete-profile`: `namabisnis`, `jenisbisnis`, `nomorhp`, `alamat`; tombol "Nanti Saja"). Dikirim ke `owner.profile.complete` (lihat §2.14). Tujuan: panduan owner baru di Beranda (langkah "Lengkapi profil").
- **Banner trial** (`trial-banner`). Tujuan: banner status di Beranda.

### 2.2 Calendar → Kalender

| Route | Aksi | Status | Tujuan |
|---|---|---|---|
| `owner.calendar` | Tampilan hari, minggu, bulan; detail booking; form walk-in (`idschedule`, `namapelanggan`, `nomorhp`, `email`, `metode`, `catatan`); tombol "Hapus Slot", "Selesai", "Batalkan" | Jalan | Kalender. Walk-in juga bisa dari tombol + Booking di bilah atas |

`metode` walk-in hanya boleh `cash`, `transfer`, `manual`, atau `qris`. Pilihan ini harus tetap ada di formulir walk-in baru.

### 2.3 Bookings → Booking dan panel detail booking

| Route | Aksi dan validasi | Status | Tujuan |
|---|---|---|---|
| `owner.bookings` | Daftar (desktop dan kartu HP), filter `katakunci` dan `status`, ringkasan angka, modal detail. Kolom tabel: Booking ID, Customer, Service, Date & Time, Staff / Resource, Amount, Payment, Status | Jalan | Booking: tab menurut tugas, pencarian, filter |
| `owner.bookings.status` | Ubah status ke `paid`, `completed`, atau `cancelled` (`UpdateBookingStatusRequest`); tombol "Konfirmasi Lunas", "Selesai", "Batalkan" di modal | Jalan. "Konfirmasi Lunas" untuk booking online sudah dihapus menurut keputusan 1 Nov 2026; sisanya dicek di Fase 3 | Panel detail booking: Tandai selesai, Tandai tidak datang (D-013), Batalkan (D-001) |
| `owner.bookings.walkin` | Catat walk-in (validasi di §2.2) | Jalan | Panel + Booking dan Kalender |
| `owner.bookings.available-slots` | Slot tersedia untuk pindah jadwal | Jalan | Panel detail booking → Pindah jadwal |
| `owner.bookings.reschedule` | Pindah jadwal: `schedule_id`, `tanggal` (hari ini atau sesudahnya), `alasan` maksimal 255 | Jalan; tidak memperhitungkan selisih harga | Panel detail booking → Pindah jadwal dengan selisih harga (D-006) |

### 2.4 Schedule → Jam Buka & Harga

| Route | Aksi dan validasi | Status | Tujuan |
|---|---|---|---|
| `owner.schedule` | Tampilan minggu, *Bulk Price Adjustments*, *Recent Activity* | Jalan. Panel harga massal tercatat palsu di D-007 versi 2 Nov | Tab Slot. Panel harga massal: Hapus. Recent Activity: riwayat booking di panel detail booking dan log aktivitas tim (D-011) |
| `owner.schedule.bulk-store` | Buat slot massal: `jenisslot` (`harian` atau `rentang`), `idlayanan`, `tanggal` atau `tanggalmulai`–`tanggalselesai`, `jammulai`, `jamselesai`, `intervalslot` 5–480 menit | Jalan (BQ-107 mencatat batasnya) | Tab Slot → Buat slot massal |
| `owner.schedule.slots.destroy` | Hapus satu slot | Jalan | Tab Slot → tutup/buka satu slot. Hapus vs tutup diputuskan di Fase 3 |
| `owner.schedule.default-pricing` | Harga default per layanan: `idlayanan`, `harga` | Jalan | Tab Harga, atau harga dasar di Layanan. **YATIM-1** |
| `owner.schedule.availability` | Tanggal tutup (`tanggal_block`, `alasan`), harga akhir pekan (`weekend_price_type`: `none`, `multiplier`, atau `fixed`; `weekend_price_value`) | Jalan (BQ-108 mencatat konflik) | Tab Libur & Tutup, dan tab Harga |
| `owner.schedule.blocked-dates.delete` | Hapus tanggal tutup | Jalan | Tab Libur & Tutup |

Catatan untuk Fase 3: Rancangan menyebut "tambahan akhir pekan, jam ramai, harga tanggal khusus". Saat ini hanya ada akhir pekan, dengan tiga jenis (`none`, `multiplier`, `fixed`). Jam ramai dan harga tanggal khusus belum ada di kode. Jenis pengali (`multiplier`) harus tetap ada.

### 2.5 Services / Programs → Layanan, tab Layanan

Ada dua set route yang sama untuk satu controller (`OwnerProgramController`):

| Route | Aksi | Status | Tujuan |
|---|---|---|---|
| `owner.services` | Daftar, cari (`katakunci`), filter `category` | Jalan | Layanan → tab Layanan |
| `owner.services.store` | Tambah (`StoreServiceRequest`) | Jalan | Sama |
| `owner.services.update` | Ubah (`UpdateServiceRequest`) | Jalan | Sama |
| `owner.services.destroy` | Hapus | Jalan | Sama. Rancangan: layanan yang punya riwayat booking hanya bisa dinonaktifkan |
| `owner.services.toggle` | Aktif/nonaktif | Jalan | Sama |
| `owner.programs` | Alias dari `owner.services` | Jalan | **Hapus** setelah tautan lama dialihkan (Rancangan §13: alamat lama tetap hidup, jadi cukup redirect) |
| `owner.programs.store` | Alias | Jalan | Sama |
| `owner.programs.update` | Alias | Jalan | Sama |
| `owner.programs.destroy` | Alias | Jalan | Sama |
| `owner.programs.toggle` | Alias | Jalan | Sama |

Field layanan:

| Field | Validasi | Tujuan |
|---|---|---|
| `namalayanan` | wajib, ≤255 | Nama |
| `harga` | wajib, ≥0 | Harga dasar |
| `durasi` | wajib, 5–480 menit | Durasi |
| `idcategory` | milik tenant | Kategori |
| `deskripsi` | ≤1000 | Deskripsi |
| `cover_image`, `remove_image` | gambar ≤10 MB | Foto dari galeri media (D-009) |
| `is_active` | wajib saat ubah | Status |
| `staff_ids[]` | milik tenant | **Hapus.** Staf tidak lagi menjadi penyedia layanan (D-011) |
| `resource_ids[]` | milik tenant | Unit yang dipakai (D-012) |
| `additional_item_ids[]` | milik tenant | Add-on terkait (D-015) |

Kolom di tabel `services` yang tidak bisa diubah dari form mana pun:
- `kapasitas`: ditampilkan di halaman pelanggan; tujuannya kapasitas per slot (D-014);
- `is_popular`: **YATIM-2**;
- `satuan_durasi`: dipakai halaman pelanggan, default `menit`; **YATIM-3**;
- `satuan_harga`: **YATIM-3**;
- `slug`.

### 2.6 Categories → Layanan, tab Kategori

| Route | Aksi | Status | Tujuan |
|---|---|---|---|
| `owner.categories` | Daftar, cari, urutkan (`sort`), filter `status` | Jalan | Tab Kategori |
| `owner.categories.store` | `name` (unik per tenant), `description` ≤500, `color` ≤50, `is_active` | Jalan | Sama |
| `owner.categories.update` | Sama | Jalan | Sama |
| `owner.categories.destroy` | Hapus | Jalan | Sama |
| `owner.categories.toggle` | Aktif/nonaktif | Jalan | Sama |

Rancangan menulis isi kategori: nama, deskripsi, gambar, status, urutan tampil. Field `color` tidak disebut (**YATIM-4**). Gambar dan urutan tampil belum ada di kode.

### 2.7 Additional Items → Layanan, tab Add-on (tersembunyi)

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.additional-items` | Daftar, cari | Tab Add-on (D-015) |
| `owner.additional-items.store` | `name` ≤150, `description` ≤500, `price` ≥0, `stock` ≥0 atau kosong, `is_active`, `service_ids[]` | Sama |
| `owner.additional-items.update` | Sama | Sama |
| `owner.additional-items.destroy` | Hapus | Sama |
| `owner.additional-items.toggle` | Aktif/nonaktif | Sama |

Tabel `additional_items` punya kolom `is_unlimited`. Stok tanpa batas harus tetap bisa dipilih.

### 2.8 Staff & Resources → Tim dan Unit (tersembunyi)

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.staff-resources` | Halaman dengan tab Staff dan Resources, cari | Dipecah: Pengaturan → Tim dan menu Unit |
| `owner.staff.store` | `name`, `role` (teks bebas), `phone`, `email`, `availability` / `availability_schedule` (pilihan shift), `is_active`, `service_ids[]` | **Artinya berubah.** Diganti undangan anggota tim dengan peran Pemilik/Manajer/Kasir (D-011). Shift dan layanan yang ditangani staf: **Hapus** |
| `owner.staff.update` | Sama | Sama |
| `owner.staff.destroy` | Hapus | Pengaturan → Tim → cabut akses |
| `owner.staff.toggle` | Aktif/nonaktif | Sama |
| `owner.resources.store` | `name`, `type`, `capacity` 1–9999, `location`, `is_active`, `service_ids[]` | Unit (D-012). Field `location` tidak disebut di Rancangan: **YATIM-5** |
| `owner.resources.update` | Sama | Sama |
| `owner.resources.destroy` | Hapus | Sama |
| `owner.resources.toggle` | Aktif/nonaktif | Sama |

### 2.9 Customers → Pelanggan

| Route | Aksi | Status | Tujuan |
|---|---|---|---|
| `owner.customers` | Ringkasan (total pelanggan unik, total belanja, total booking), cari, tabel (telepon/WhatsApp, email, jumlah booking, total belanja, booking terakhir, booking mendatang) | Jalan | Pelanggan |
| `owner.customers.detail` | Panel detail dengan tab Overview, Booking History, Payments, Notes | Jalan | Panel detail pelanggan |
| `owner.customers.note` | Catatan internal: `customer_identifier`, `notes` ≤2000 | Jalan | Sama |

### 2.10 Vouchers → Promo (tersembunyi)

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.vouchers` | Daftar, cari | Promo (D-015), dengan tab Aktif/Terjadwal/Berakhir/Semua dan statistik |
| `owner.vouchers.store` | `code` (unik per tenant, `alpha_dash`, ≤50), `discount_type` (`fixed` atau `percentage`), `discount_value` ≥1, `min_order_amount`, `max_discount_amount`, `usage_limit`, `start_date`, `end_date`, `is_active`, `applicable_services[]` | Sama |
| `owner.vouchers.update` | Sama | Sama |
| `owner.vouchers.destroy` | Hapus | Sama |
| `owner.vouchers.toggle` | Aktif/nonaktif | Sama |

Tabel `vouchers` juga punya kolom `used_count`. Ini sumber angka kuota terpakai.

### 2.11 Reviews → Ulasan

| Route | Aksi | Status | Tujuan |
|---|---|---|---|
| `owner.reviews` | Rata-rata, sebaran rating, cari, daftar | Jalan | Ulasan |
| `owner.reviews.reply` | `balasan` wajib, ≤1000 | Jalan | Balas (dengan contoh kalimat) |
| `owner.reviews.toggle` | Sembunyikan/tampilkan | Jalan | Tab Disembunyikan |

### 2.12 Analytics → Laporan (terkunci, Medium)

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.analytics` | Estimasi pendapatan, tingkat konversi, volume transaksi | Laporan → Ringkasan dan Pendapatan |
| `owner.analytics.export` | Ekspor CSV (BQ-111 mencatat hitung ganda dan CSV injection) | Laporan → Ekspor |

### 2.13 Schedule Report → Laporan, tab Okupansi (terkunci)

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.schedule-report` | Tingkat utilisasi, total slot, slot terpesan, slot kosong | Laporan → Okupansi |
| `owner.schedule-report.export` | Ekspor | Laporan → Ekspor |

### 2.14 Settings → Pengaturan

| Route | Aksi dan validasi | Status | Tujuan |
|---|---|---|---|
| `owner.settings` | Halaman pengaturan (sama dengan `owner.settings.business`) | Jalan | Pengaturan |
| `owner.settings.business` | Business Profile, Account Settings, Notification Preferences, Danger Zone | Jalan; **Notification Preferences palsu** (tidak tersimpan) | Tab Profil Bisnis, Akun, Notifikasi (disimpan di server) |
| `owner.settings.profile` | `namabisnis`, `jenisbisnis`, `nomorhp`, `alamat`, `deskripsi`, `logo` (gambar ≤10 MB); slug tampil | Jalan. Request menerima `logo`, tetapi form tidak punya input logo | Tab Profil Bisnis. Logo pindah ke Halaman Booking → Tampilan (D-009) |
| `owner.profile.complete` | Lengkapi profil pertama kali (`namabisnis`, `jenisbisnis`, `nomorhp`, `alamat`) | Jalan | Panduan owner baru di Beranda |
| `owner.settings.account` | `namalengkap`, `email` (unik), `nomorhp`, `password` + `password_confirmation` (opsional, ≥8) | Jalan; BQ-098: belum meminta password saat ini | Tab Akun |
| `owner.settings.account.delete` | Hapus akun dengan konfirmasi email (`confirm_account`); ditolak bila saldo ≠ 0 atau ada pencairan berjalan (BQ-122) | Jalan; BQ-098 | Tab Akun → hapus akun (Pemilik saja) |

Kolom di tabel `tenants` yang tidak bisa diubah dari form mana pun:
- `cancel_before_hours` dan `reschedule_before_hours`. Keduanya dipakai `BookingRules`, `CancelBooking`, dan halaman kelola booking pelanggan. Tujuan: Pengaturan → Kebijakan Booking (D-001 poin 9). Ini kebutuhan nyata yang saat ini tidak punya tempat sama sekali.

### 2.15 Appearance → Halaman Booking, Tampilan (tersembunyi)

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.settings.appearance` | Pratinjau, pilihan gaya | Halaman Booking → Tampilan (D-009) |
| `owner.settings.appearance.update` | `theme_color`, `button_style` (pill/rounded/square), `card_style` (elevated/outline/soft), `font_family`, `deskripsi` | Sama. Gaya tombol, gaya kartu, dan huruf harus tetap ada (Rancangan hanya menyebut "gaya") |

### 2.16 Assets → Galeri media (tersembunyi)

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.settings.assets` | Daftar, filter, cari, salin URL | Halaman Booking → Galeri media, dan pemilih gambar di setiap unggahan |
| `owner.settings.assets.store` | `file` (gambar ≤10 MB), `title` ≤150, `category` (`logo`, `cover`, `service`, `gallery`, `other`) | Sama |
| `owner.settings.assets.destroy` | Hapus | Sama |

### 2.17 Landing Page → Halaman Booking, Landing page (tersembunyi, Pro)

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.landing-page` | Pilih template (Modern Business, Creative Studio, Minimalist Clinic), bagian halaman, pratinjau | Halaman Booking → Landing page (Pro) |
| `owner.landing-page.store` | `custom_domain` (unik), `theme_color`, `banner` (gambar ≤10 MB) | Sama, dengan domain sendiri (D-005) |

### 2.18 Payment settings (tersembunyi) → Pembayaran, sebagian dihapus

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.settings.payments` | Pilihan gateway, kredensial Midtrans, riwayat penarikan, riwayat transaksi pembayaran, form tarik saldo lama | Riwayat transaksi: Pembayaran → Transaksi. Gateway dan kredensial: **Hapus** (D-016). Form tarik saldo lama: **Hapus** (diganti BQ-123 sampai BQ-125) |
| `owner.settings.payment-setting` | Alias halaman yang sama | **Hapus** |
| `owner.settings.payment` | Simpan `payment_mode` (`platform` atau `owner`), `midtrans_environment`, kredensial sandbox dan produksi | **Hapus** (D-016) |

### 2.19 Saldo & withdraw → Pembayaran (dirilis)

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.settings.balance` | Saldo tersedia, dalam proses, total pendapatan; syarat penarikan; daftar rekening; riwayat dengan pagination 15 | Pembayaran → Ringkasan, Saldo & Pencairan, Rekening |
| `owner.payouts.request` | `request_key` (UUID), `payout_account_id` (milik tenant), `jumlah` bulat ≥10.000; dialog bertahap | Pembayaran → Saldo & Pencairan |
| `owner.payouts.proof` | Unduh bukti transfer milik tenant sendiri | Sama |
| `owner.payout-accounts.store` | `bank_kode` (daftar `config/banks.php`), `nomor_rekening` 5–30 digit, `nama_pemilik_rekening` ≤150, `current_password` | Pembayaran → Rekening (D-004) |
| `owner.payout-accounts.update` | Sama | Sama |
| `owner.payout-accounts.destroy` | `current_password`; ditolak bila rekening dipakai pencairan yang sedang berjalan | Sama |
| `owner.payout-accounts.default` | Jadikan rekening utama | Sama (BQ-127: aturan throttle) |

### 2.20 Integrations → Pengaturan, tab Integrasi (terkunci)

| Route | Aksi | Status | Tujuan |
|---|---|---|---|
| `owner.settings.integrations` | Kartu integrasi statis ("segera hadir") dan status sistem | Palsu | Pengaturan → Integrasi dengan koneksi nyata (D-019) |

### 2.21 Subscription dan checkout → Paket & Tagihan

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.subscription` | Paket aktif, paket tersedia, riwayat tagihan | Paket & Tagihan |
| `owner.checkout` | Ringkasan paket | Paket & Tagihan → naik/turun paket |
| `owner.checkout.process` | `plan_id`, `nama_pembayar`, `email_pembayar`, `hp_pembayar`, `catatan` | Sama |
| `owner.checkout.payment` | Halaman bayar dengan hitung mundur, tombol "Bayar Sekarang" | Sama |
| `owner.checkout.check-status` | Cek status pembayaran ke provider | Sama |
| `owner.checkout.callback` | Kembali dari provider | Sama |
| `owner.checkout.invoice` | Invoice langganan | Paket & Tagihan → riwayat tagihan |

### 2.22 Notifications → Lonceng

| Route | Aksi | Tujuan |
|---|---|---|
| `owner.notifications` | Pusat notifikasi | Lonceng, ditambah halaman daftar lengkap |
| `owner.notifications.read` | Tandai terbaca | Sama. Mengklik notifikasi juga membuka tujuannya |
| `owner.notifications.read-all` | Tandai semua terbaca | Sama |
| `owner.notifications.destroy` | Hapus notifikasi | Sama |

### 2.23 Bilah atas dan sidebar

- **`topbar`:** tautan ke dashboard, notifikasi, pengaturan bisnis, langganan, dan keluar. Tujuan: bilah atas baru dengan lonceng, + Booking, pencarian (kode booking, nama, nomor HP), dan menu akun.
- **`sidebar`:** tautan langganan dan keluar, ditambah menu modul. Tujuan: 15 menu dalam 5 grup, dan navigasi bawah di HP.

---

## 3. Yang belum punya tempat di Rancangan (YATIM)

Diputuskan di Fase 3: diberi tempat, atau dihapus dengan alasan.

| ID | Fitur | Usulan |
|---|---|---|
| YATIM-1 | Harga default per layanan dari halaman Schedule (`owner.schedule.default-pricing`) | Gabung ke harga dasar di Layanan. Endpoint ini menyetel harga slot yang sudah ada, jadi Fase 3 memastikan perilaku "terapkan ke slot yang sudah dibuat" tetap tersedia di tab Slot |
| YATIM-2 | `services.is_popular` | Penanda "Terpopuler" di tab Layanan, atau dihapus. Kolom ini tidak punya form |
| YATIM-3 | `services.satuan_durasi`, `satuan_harga` | Pertahankan sebagai tampilan di halaman pelanggan dengan default tetap, atau jadikan field. Perlu dicek di tampilan pelanggan |
| YATIM-4 | `categories.color` | Pertahankan sebagai label warna di tab Kategori |
| YATIM-5 | `resources.location` | Pertahankan di Unit, misalnya "Lantai 2" |
| YATIM-6 | `cancel_before_hours`, `reschedule_before_hours` tanpa form | Pengaturan → Kebijakan Booking (D-001 poin 9). Tempatnya sudah jelas, tetapi saat ini tidak ada form untuk mengubahnya |
| YATIM-7 | Panel *Recent Activity* di Schedule | Riwayat di panel detail booking dan log aktivitas tim (D-011) |

---

## 4. Fitur lama yang tidak tampil di Prototype

Dicek dengan mencari teks di `prototype-portal-owner.html`. Semuanya ditambahkan di Fase 7, setelah tempatnya ditetapkan di Fase 3.

| Fitur lama | Keterangan |
|---|---|
| Buat slot massal dengan jam mulai, jam selesai, interval, dan pilihan harian/rentang | Prototype hanya meminta tanggal mulai dan jumlah hari |
| Harga akhir pekan jenis pengali (`multiplier`) | Prototype hanya punya tambahan nominal |
| Gaya tombol, gaya kartu, dan jenis huruf halaman booking | Tidak ada di Prototype |
| Nomor HP dan konfirmasi password baru di Akun | Prototype hanya punya nama, email, password |
| Kategori aset, judul aset, dan salin URL di galeri | Tidak ditemukan |
| Data pembayar (nama, email, HP) saat membeli paket | Prototype langsung ke pembayaran contoh |
| Batas minimal pencairan Rp 10.000 | Prototype memakai Rp 50.000, dan hanya satu rekening (D-004: sampai 3) |
| Pilihan refund saat owner membatalkan | Prototype masih punya "Tanpa refund" dan "Sesuai kebijakan"; harus mengikuti D-001 |
| Konfirmasi hapus akun dengan email | Prototype memakai nama bisnis dan password |

---

## 5. Data yang sudah ada di modul tersembunyi

Diperiksa dari skema dan model saja (Rancangan §12 poin 3). Sebelum modulnya diaktifkan, work item epic harus menghitung isinya di produksi lewat jalur baca-saja dan memutuskan cara migrasinya.

| Tabel / kolom | Isi | Akibat |
|---|---|---|
| `staff` + pivot layanan | Staf sebagai penyedia layanan (nama, peran teks bebas, shift) | Artinya berubah (D-011). Data tidak bisa langsung menjadi anggota tim, karena anggota tim butuh akun login. Usulan: diarsipkan, lalu owner mengundang ulang |
| `resources` + pivot layanan | Sumber daya (tipe, kapasitas, lokasi) | Bisa langsung menjadi Unit (D-012) |
| `vouchers` | Kode promo, termasuk `used_count` | Bisa langsung menjadi Promo (D-015). `used_count` dari masa sebelum checkout menghitung promo perlu dicek |
| `additional_items` + pivot layanan | Add-on, stok, `is_unlimited` | Bisa langsung menjadi Add-on (D-015) |
| `assets` | Berkas gambar per tenant | Menjadi galeri media (D-009) |
| `tenants.theme_color`, `button_style`, `card_style`, `font_family`, `banner_path`, `logo_path`, `custom_domain` | Tampilan dan landing page | Dipakai Halaman Booking (D-005, D-009) |
| `tenants.payment_mode`, `midtrans_*` | **Kredensial gateway milik owner**, termasuk server key | Dihapus bersama BYOPG (D-016). Work item-nya harus menghapus kredensial yang tersimpan dengan aman, bukan hanya menyembunyikan form |

---

## 6. Bagian portal pelanggan dan admin yang tersentuh

| Route | Saat ini | Tujuan |
|---|---|---|
| `customer.booking.process-checkout` (dan versi `{slug_usaha}`) | `CreateBookingRequest` menerima `selected_addons` dan `voucher_code`, tetapi **tidak ada kode yang memakainya** | Checkout menghitung promo dan add-on (D-015) |
| `customer.booking.validate-voucher` (dan versi `{slug_usaha}`) | Modul `vouchers`; selalu menjawab "Fitur voucher saat ini belum aktif." | Validasi promo nyata (D-015) |
| `booking.manage.cancel` | Pembatalan oleh pelanggan langsung mencatat refund | Pengajuan refund (D-001) |
| `booking.manage.reschedule.show`, `booking.manage.reschedule.store`, `booking.manage.reschedule.slots` | Hanya booking `paid`; tidak memperhitungkan harga | Hanya ke slot dengan harga sama atau lebih murah (D-006) |
| `booking.manage`, `booking.manage.invoice`, `booking.manage.payment`, `booking.manage.payment.invoice`, `booking.manage.review` | Jalan | Tetap; menampilkan status pengajuan refund dan status Tidak datang |
| `customer.booking.*` (storefront) | Jalan | Menampilkan aturan D-020, kapasitas D-014, tampilan D-009, pesan D-002 saat langganan habis |
| `admin.payouts`, `admin.payouts.approve`, `admin.payouts.complete`, `admin.payouts.fail`, `admin.payouts.reject` | Jalan (BQ-125) | Tetap. Ditambah halaman pemrosesan refund untuk admin (D-001 poin 7) |
| `admin.dashboard` | Ringkasan, termasuk jumlah pencairan yang menunggu | Ditambah jumlah pengajuan refund yang menunggu (D-001) |

---

## 7. Temuan tambahan untuk Fase 3 dan Fase 9

1. **Field yang diterima tetapi tidak dipakai.** `voucher_code` dan `selected_addons` lolos validasi checkout tetapi diabaikan. Kalau checkout dibuka sebelum D-015 selesai, pelanggan bisa mengira promonya berlaku.
2. **Route ganda.** `owner.programs.*` menggandakan `owner.services.*`, dan `owner.settings.payment-setting` menggandakan `owner.settings.payments`. Spesifikasi baru menetapkan satu alamat, dan alamat lama dialihkan.
3. **Field yang diterima tetapi tidak ada inputnya.** `UpdateBusinessProfileRequest` menerima `logo`, tetapi form Profil Bisnis tidak punya input logo.
4. **Kontrol palsu yang masih tampil:**
   - Notification Preferences di Pengaturan;
   - panel *Bulk Price Adjustments* di Schedule;
   - kartu di halaman Integrations.
5. **Aturan pembatalan yang tidak bisa diatur.** Batas jam pembatalan dan pindah jadwal sudah dipakai sistem, tetapi owner tidak bisa mengubahnya (YATIM-6).

---

## Pemeriksaan cakupan

- Jumlah route owner di `php artisan route:list --path=owner`: **93**.
- Hasil pemeriksaan otomatis 4 Nov 2026: nama route di JSON `route:list` dibandingkan dengan kolom pertama setiap tabel di §2. **Semua 93 route muncul tepat satu kali, tidak ada yang terlewat, dan tidak ada yang ganda.** Jumlah per halaman di §1 juga berjumlah 93.
