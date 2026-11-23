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
  <img src="https://img.shields.io/badge/Payment-SingaPay_%7C_Midtrans-002855?style=for-the-badge" alt="SingaPay | Midtrans">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License MIT">
</p>

---

## 📖 Tentang BookQu

**BookQu** adalah platform SaaS *multi-tenant* yang dirancang untuk mempermudah operasional berbagai jenis bisnis berbasis reservasi dan persewaan waktu—seperti studio foto & musik, sewa lapangan olahraga, salon, klinik kecantikan, ruang kerja (*coworking space*), hingga jasa konsultasi profesional.

Dengan BookQu, setiap pemilik bisnis (*Tenant/Owner*) memiliki halaman etalase publik tersendiri (`/{tenant-slug}`), sistem penentuan slot jadwal fleksibel, penerimaan pembayaran digital otomatis melalui gateway terpadu (**SingaPay** sebagai default, **Midtrans** sebagai alternatif), serta portal dasbor komprehensif untuk mengelola kasir *walk-in* dan jadwal di tempat secara *real-time*.

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
* **Saldo & Withdraw**: Pembayaran booking yang sukses masuk ke saldo owner (fee 0%); owner mengajukan withdraw ke rekening bank, admin mereview dan mentransfer manual dengan bukti.
* **Modul Bisnis Pendukung** (dirilis bertahap dan dikunci di server lewat `config/modules.php` — Tier 2 terkunci / Tier 3 tersembunyi (404), lihat `docs/product.md` §8):
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
* **Alur Booking 3 Langkah**: Layanan → Jadwal (pilih tanggal lalu satu atau beberapa slot berurutan di satu halaman, slot dimuat langsung dari database) → Data & Pembayaran. Pelanggan yang meninggalkan pembayaran bisa melanjutkannya sebelum batas waktu.
* **Checkout & Integrasi Pembayaran**: Gateway terpadu — SingaPay (payment link, QRIS, Virtual Account, e-wallet) atau Midtrans Snap, dipilih lewat `PAYMENT_GATEWAY_DRIVER`.
* **Konfirmasi & Kode Booking Unik**: Generate kode booking terstruktur (contoh: `#BKQ-20260910-XYZ123`).
* **Self-Service Customer Portal**: Halaman kelola reservasi mandiri lewat tautan akses aman untuk membatalkan atau menjadwalkan ulang sesuai kebijakan batas waktu bisnis.

### 3. 🛡️ Admin Panel (`/admin`)
* Dasbor platform: jumlah tenant, status langganan (*Small*, *Medium*, *Pro*), dan pendapatan langganan.
* Review withdraw owner (`/admin/payouts`): setujui, catat transfer dan bukti, atau tolak dengan alasan.

---

## 🛠️ Tech Stack

* **Backend**: [Laravel 13](https://laravel.com) (PHP 8.3+)
* **Database**: MySQL 8.0+ / MariaDB 10.4+
* **Frontend**: [Tailwind CSS v4](https://tailwindcss.com), [Alpine.js 3.x](https://alpinejs.dev), Blade Templating
* **Asset Bundler**: [Vite 8](https://vitejs.dev)
* **Payment Gateway**: SingaPay (HTTP API) dan [Midtrans PHP SDK](https://github.com/Midtrans/midtrans-php) di balik `PaymentGatewayInterface` (ADR-0001)
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

> **Penting — `APP_URL` harus sama persis dengan alamat yang Anda buka di browser.** Pakai `http://localhost:8000` untuk `php artisan serve`, atau `http://bookqu.test` untuk Laragon. Link akses pelanggan (halaman pembayaran, invoice, kelola booking) hanya berlaku di host `APP_URL`; kalau berbeda, halaman itu menjawab "Halaman tidak tersedia" dan redirect setelah bayar menuju alamat yang salah.

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

#### Langkah 8: Konfigurasi Payment Gateway (Opsional / Sandbox)
Pilih driver dengan `PAYMENT_GATEWAY_DRIVER=singapay` (default) atau `midtrans`, lalu isi kredensial sandbox provider tersebut di `.env`. Daftar lengkap variabel ada di `docs/operations.md` §2. Kredensial sandbox dibagikan di luar repo (pesan langsung atau password manager) dan tidak pernah di-commit.

Untuk SingaPay sandbox:
* Gunakan `SINGAPAY_BASE_URL=https://sandbox-payment-b2b.singapay.id` beserta kredensial dari dashboard **sandbox** (kredensial sandbox tidak berlaku di URL produksi).
* Daftarkan IP publik laptop Anda di dashboard SingaPay (Credential → IP Address Configuration). IP yang dilihat SingaPay bisa berbeda dari situs cek IP; pesan error SingaPay di `storage/logs/laravel.log` menyebutkan IP yang harus didaftarkan.
* SingaPay tidak bisa mengirim webhook ke laptop. Untuk menandai pembayaran uji sebagai lunas, jalankan `php artisan singapay:simulate-webhook <order_id>` (lihat `docs/operations.md` §1).

#### Langkah 9: Email di Lokal
Email (konfirmasi, link akses pelanggan, notifikasi owner) dikirim lewat antrean. Dengan `MAIL_MAILER=log` isi email ditulis ke `storage/logs/laravel.log`. `composer run dev` sudah menjalankan antrean; kalau memakai Laragon tanpa perintah itu, jalankan `php artisan queue:work --stop-when-empty` untuk memproses email yang menunggu.

#### Setiap Kali Menarik Kode Terbaru
```bash
git pull
composer install
npm install
php artisan migrate
php artisan optimize:clear
npm run build
```

---

### 3. Menjalankan Aplikasi di Lokal

Cara tercepat — satu perintah untuk server, queue, log, dan Vite:
```bash
composer run dev
```

Atau jalankan dua perintah berikut di terminal terpisah:

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
├── AGENTS.md                      # Panduan kerja untuk agent AI & kontributor (wajib dibaca)
├── .ai/rules/                     # Aturan per area kode untuk agent
├── docs/                          # Dokumentasi produk, requirement, arsitektur, domain, operasional, backlog
├── app/
│   ├── Actions/                   # Operasi aplikasi (CreateBooking, ExpirePayment, …)
│   ├── Domain/                    # Aturan bisnis murni (BookingRules, PaymentState, EntitlementRules, …)
│   ├── Infrastructure/Payments/   # Driver SingaPay & Midtrans
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

## 📚 Dokumentasi

Mulai dari [`AGENTS.md`](AGENTS.md) lalu [`docs/README.md`](docs/README.md). Ringkasnya:

| Pertanyaan | Dokumen |
| :--- | :--- |
| Apa itu BookQu & fitur mana yang sudah rilis? | [`docs/product.md`](docs/product.md) |
| Apa yang wajib dilakukan sistem? | [`docs/requirements.md`](docs/requirements.md) |
| Bagaimana kode harus disusun? | [`docs/architecture.md`](docs/architecture.md) |
| Bagaimana sebuah domain bekerja saat ini? | [`docs/domains/`](docs/domains/README.md) |
| Arti istilah & nama kolom lama | [`docs/glossary.md`](docs/glossary.md) |
| Cara kerja tim (rencana, persetujuan, PR) | [`docs/workflow.md`](docs/workflow.md) |
| Deploy & operasional | [`docs/operations.md`](docs/operations.md) |
| Rencana & backlog | [`docs/work/ROADMAP.md`](docs/work/ROADMAP.md) |

---

## 🤝 Kontribusi

Peran dan siapa memutuskan apa ada di [`AGENTS.md`](AGENTS.md) → "Roles"; proses lengkap di [`docs/workflow.md`](docs/workflow.md).

| Branch | Fungsi | Cara perubahan masuk |
| :--- | :--- | :--- |
| `main` | Produksi — setiap push langsung ter-deploy | Hanya PR rilis dari `integration` |
| `integration` | Pusat penggabungan hasil kerja semua developer | PR + 4 cek CI hijau, diaudit dan di-merge Tech Lead |
| `agathan`, `bagus` | Ruang eksplorasi masing-masing developer | Push bebas oleh pemiliknya |

Aturan branch pribadi: tarik `integration` terbaru sebelum mulai tugas, ajukan satu tugas per PR, dan tarik `integration` lagi sebelum membuka PR. **Jangan push langsung ke `main` atau `integration`.**

Agent AI (Claude Code, Codex, Antigravity, Cursor) membaca `AGENTS.md` otomatis. Memori, skill, atau plugin pribadi di komputer masing-masing tidak menggantikan aturan repo; kalau bertentangan, aturan repo yang berlaku.

---

## 📄 Lisensi

Project ini dilisensikan di bawah MIT License.
