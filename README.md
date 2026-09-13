# BookQu — Modern Multi-Tenant Booking & Reservation Management Platform

<p align="center">
  <img src="public/favicon.ico" width="80" height="80" alt="BookQu Logo" style="border-radius: 20px; box-shadow: 0 10px 25px rgba(56, 33, 134, 0.2);">
</p>

<p align="center">
  <strong>Solusi terpadu manajemen jadwal, reservasi pelanggan, transaksi walk-in kasir, dan integrasi pembayaran online multi-tenant.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Tailwind_CSS-v4.0-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS v4">
  <img src="https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=white" alt="Alpine.js">
  <img src="https://img.shields.io/badge/Payment-Midtrans_Snap-002855?style=for-the-badge" alt="Midtrans">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License MIT">
</p>

---

## 📖 Tentang BookQu

**BookQu** adalah platform SaaS *multi-tenant* yang dirancang untuk mempermudah operasional berbagai jenis bisnis berbasis reservasi dan persewaan waktu—seperti studio foto & musik, sewa lapangan olahraga, salon, klinik kecantikan, ruang kerja (*coworking space*), hingga jasa konsultasi profesional.

Dengan BookQu, setiap pemilik bisnis (*Tenant/Owner*) memiliki halaman etalase publik tersendiri (`/{tenant-slug}`), sistem penentuan slot jadwal fleksibel, penerimaan pembayaran digital otomatis melalui **Midtrans Payment Gateway**, serta portal dasbor komprehensif untuk mengelola kasir *walk-in* dan jadwal di tempat secara *real-time*.

---

## ✨ Fitur-Fitur Utama

### 1. 🏢 Portal Owner & Manajemen Bisnis (`/owner`)
* **Dashboard Analitik**: Ringkasan omzet, rasio okupansi slot, grafik booking live harian, dan antrean reservasi masuk.
* **Calendar Interaktif**: Tampilan kalender fleksibel (*Week*, *Month*, *Day*) dengan filter layanan, status pembayaran, dan visualisasi slot terisi.
* **Schedule Management & Bulk Slots**:
  - Generator slot jadwal massal (*harian* dan *rentang tanggal*).
  - Pengecekan otomatis anti-tabrakan jadwal (*slot conflict prevention*).
  - Pengaturan tarif khusus akhir pekan (*weekend surge pricing / discount*).
  - Fitur blokir hari libur/maintenance toko (*Owner Blocked Dates*).
* **Bookings Management**:
  - Daftar lengkap transaksi reservasi dengan pencarian nama, kode booking, dan status.
  - **Walk-in Booking**: Fitur kasir untuk mencatat pengunjung yang datang langsung di tempat dengan metode bayar Cash, QRIS, atau Transfer.
  - **Direct Reschedule Khusus Owner**: Pemilik dapat memindahkan tanggal atau slot jam booking tamu *walk-in* secara langsung tanpa perlu token mandiri pelanggan dan tanpa terhalang batas jam minimal.
  - **Modal Detail Terpusat**: Ringkasan data pemesan, rincian biaya, catatan khusus, konfirmasi lunas, tanda selesai, dan cetak invoice.
* **Katalog Services (Layanan & Program)**:
  - Pengaturan nama layanan, tarif, durasi menit, kapasitas, dan status aktif/nonaktif.
  - Tampilan cover gambar persegi proporsional (rasio 1:1) dengan upload media otomatis.
  - Proteksi hapus: mencegah penghapusan layanan jika masih ada reservasi aktif.
* **Modul Bisnis Pendukung**:
  - **Service Categories**: Pengelompokan jenis layanan.
  - **Staff & Resources**: Penugasan staf dan aset/ruangan khusus pada layanan tertentu.
  - **Additional Items (Add-ons)**: Upselling barang/jasa sewa tambahan pada booking.
  - **Vouchers & Promo Discounts**: Pembuatan kupon diskon nominal atau persentase.
  - **Assets Manager**: Pengelolaan media, logo bisnis, dan banner promosi.
  - **Pusat Notifikasi**: Notifikasi *real-time* booking masuk dan perubahan jadwal.
  - **Customers Directory**: Riwayat pemesan dan catatan loyalitas pelanggan.
  - **Laporan & Ekspor**: Ekspor data laporan operasional dan jadwal ke format spreadsheet.
  - **Landing Page & Branding Editor**: Kustomisasi tampilan halaman publik etalase bisnis.

### 2. 🛍️ Portal Pelanggan Publik (`/{tenant-slug}`)
* **Etalase Layanan Responsif**: Tampilan responsif di ponsel, tablet, maupun desktop.
* **Pemilih Tanggal & Slot Real-time**: Ketersediaan slot jam dicek secara dinamis langsung dari database.
* **Checkout & Integrasi Pembayaran**: Terhubung dengan Midtrans Snap API (QRIS, GoPay, ShopeePay, Virtual Account BCA/Mandiri/BNI/BRI, Kartu Kredit).
* **Konfirmasi & Kode Booking Unik**: Generate kode booking terstruktur (contoh: `#BKQ-20260910-XYZ123`).
* **Self-Service Customer Portal**: Halaman kelola reservasi mandiri dengan tautan aman ber-token untuk membatalkan atau menjadwalkan ulang sesuai kebijakan batas waktu bisnis.

### 3. 🛡️ Superadmin Panel (`/admin`)
* Pengelolaan seluruh tenant, monitoring paket langganan (*Small*, *Medium*, *Pro*), dan manajemen akun sistem.

---

## 🛠️ Tech Stack

* **Backend**: [Laravel 13](https://laravel.com) (PHP 8.3+)
* **Database**: MySQL 8.0+ / MariaDB 10.4+
* **Frontend**: [Tailwind CSS v4](https://tailwindcss.com), [Alpine.js 3.x](https://alpinejs.dev), Blade Templating
* **Asset Bundler**: [Vite 8](https://vitejs.dev)
* **Payment Gateway**: [Midtrans PHP SDK](https://github.com/Midtrans/midtrans-php) (`midtrans/midtrans-php`)
* **Arsitektur Multi-Tenant**: Tenant Context Scoping & Dynamic Routing

---

## 💻 Panduan Instalasi Lokal (Local Setup Guide)

Ikuti langkah-langkah berikut untuk menjalankan project BookQu di komputer lokal Anda:

### 1. Prasyarat Sistem (Prerequisites)
Pastikan perangkat Anda telah terpasang:
* **PHP >= 8.3** dengan ekstensi aktif:
  - `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd` / `imagick`
* **Composer** (versi 2.x) 👉 [https://getcomposer.org](https://getcomposer.org)
* **Node.js** (LTS versi 18 atau 20+) & **NPM** 👉 [https://nodejs.org](https://nodejs.org)
* **MySQL / MariaDB** (dapat menggunakan **Laragon**, **XAMPP**, atau MySQL Standalone)
* **Git** 👉 [https://git-scm.com](https://git-scm.com)

---

### 2. Langkah-Langkah Instalasi

#### Langkah 1: Clone Repositori
Buka terminal / Git Bash / Command Prompt, lalu jalankan:
```bash
git clone https://github.com/BagusAth/bookqu.git
cd bookqu
```

#### Langkah 2: Install Dependensi PHP (Composer)
```bash
composer install
```

#### Langkah 3: Install Dependensi JavaScript (NPM)
```bash
npm install
```

#### Langkah 4: Konfigurasi File Environment (`.env`)
Salin file `.env.example` menjadi `.env`:
* **Di Windows (Command Prompt / PowerShell)**:
  ```cmd
  copy .env.example .env
  ```
* **Di macOS / Linux / Git Bash**:
  ```bash
  cp .env.example .env
  ```

Buka file `.env` yang baru dibuat dengan teks editor Anda, lalu sesuaikan koneksi database:
```dotenv
APP_NAME=BookQu
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bookqu
DB_USERNAME=root
DB_PASSWORD=
```
> **Catatan**: Pastikan Anda telah membuat database kosong bernama `bookqu` di MySQL/phpMyAdmin lokal Anda sebelum melanjutkan.

#### Langkah 5: Generate Application Key
```bash
php artisan key:generate
```

#### Langkah 6: Hubungkan Penyimpanan Media Publik (Storage Link)
Buat symlink dari folder storage ke direktori public agar gambar layanan dan logo dapat diakses oleh browser:
```bash
php artisan storage:link
```

#### Langkah 7: Jalankan Migrasi & Seeder Database
Eksekusi migrasi tabel dan isi data awal (akun demo, tenant, layanan, paket langganan, dan slot jadwal):
```bash
php artisan migrate:fresh --seed
```
*(Opsional)* Jika Anda ingin menyertakan sampel data lengkap untuk studio kreatif (Brama Studio Creative):
```bash
php artisan db:seed --class=BramaStudioSeeder
```

#### Langkah 8: Konfigurasi Midtrans (Opsional / Sandbox)
Untuk menguji transaksi pembayaran online, masukkan kunci API Midtrans Sandbox Anda ke file `.env`:
```dotenv
MIDTRANS_MERCHANT_ID=your_merchant_id
MIDTRANS_CLIENT_KEY=your_client_key
MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_IS_PRODUCTION=false
```

---

### 3. Menjalankan Aplikasi di Lokal

Jalankan dua perintah berikut di terminal terpisah:

**Terminal 1 — Server Laravel Backend:**
```bash
php artisan serve
```
Aplikasi backend akan berjalan pada: `http://localhost:8000` (atau `http://127.0.0.1:8000`).

> *Jika menggunakan **Laragon**, Anda dapat langsung mengaksesnya melalui domain otomatis:* `http://bookqu.test`.

**Terminal 2 — Vite Frontend Asset Server:**
```bash
npm run dev
```

---

## 🔑 Akun Demo Bawaan (Default Accounts)

Setelah menjalankan seeder, Anda dapat langsung masuk (*login*) menggunakan kredensial berikut:

| Role | Email | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@bookqu.com` | `password` | Akses Dashboard Admin platform (`/admin`) |
| **Owner (Studio)** | `brama@bookqu.test` | `password` | Akses Dashboard Owner (`/owner`) Tenant Brama Studio Creative |
| **Owner (Lapangan)**| `badminton.owner@bookqu.test` | `password` | Akses Dashboard Owner (`/owner`) Tenant Sewa Lapangan Badminton |
| **Owner (Musik)** | `studio.owner@bookqu.test` | `password` | Akses Dashboard Owner (`/owner`) Tenant Studio Musik Harmoni |

### Contoh Halaman Etalase Publik Pelanggan:
* `http://localhost:8000/sewa-lapangan-badminton`
* `http://localhost:8000/studio-musik`
* `http://localhost:8000/brama-studio-creative` (jika menjalankan `BramaStudioSeeder`)

---

## 📁 Struktur Direktori Utama

```text
bookqu/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/             # Controller khusus Superadmin
│   │   │   ├── Customer/          # Controller etalase & booking customer
│   │   │   └── Owner/             # Controller modul Owner (Schedule, Bookings, Calendar, dll)
│   │   └── Middleware/            # Tenant scoping, subscription guards, auth middleware
│   ├── Models/                    # Eloquent Models (Booking, Schedule, Service, Tenant, Payment, dll)
│   └── Traits/                    # Reusable logic (ClearsBookingCache, ResolvesOwnerTenant)
├── config/                        # File konfigurasi Laravel & Midtrans
├── database/
│   ├── migrations/                # Schema database terstruktur
│   └── seeders/                   # Seeder data awal & demo
├── public/                        # Entrypoint web & symlink storage
├── resources/
│   ├── css/                       # Konfigurasi Tailwind CSS v4
│   ├── js/                        # Inisialisasi Alpine.js & script frontend
│   └── views/
│       ├── components/            # Blade UI components (header, modal, sidebar, topbar)
│       ├── customer/              # Tampilan etalase publik & checkout
│       ├── owner/                 # Halaman dasbor & manajemen owner
│       └── layouts/               # Master layout aplikasi
├── routes/
│   └── web.php                    # Definisi routing aplikasi
└── tests/                         # Automated unit & feature tests
```

---

## ⚡ Perintah Penting (Useful Commands)

```bash
# Membersihkan seluruh cache konfigurasi, view, dan route
php artisan optimize:clear

# Menjalankan build bundle CSS & JS untuk production
npm run build

# Menjalankan unit & functional tests
php artisan test
```

---

## 🤝 Kontribusi

1. Fork repositori ini
2. Buat branch fitur baru (`git checkout -b feature/FiturBaru`)
3. Lakukan commit perubahan Anda (`git commit -m 'Menambahkan FiturBaru'`)
4. Push ke branch Anda (`git push origin feature/FiturBaru`)
5. Buka **Pull Request**

---

## 📄 Lisensi

Project ini dilisensikan di bawah [MIT License](LICENSE).
