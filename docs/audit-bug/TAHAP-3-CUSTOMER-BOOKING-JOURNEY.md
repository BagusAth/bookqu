# Dokumentasi Audit Cacat: Tahap 3
## Customer Booking Journey: Storefront & 6-Langkah Reservasi hingga Checkout

- **Lingkup**: Halaman Pemilihan Program (`program-selection.blade.php`), Tanggal (`date-selection.blade.php`), Jam & Slot (`time-selection.blade.php`), Formulir Data Diri (`checkout.blade.php`), Pembayaran (`payment.blade.php`), Invoice Tiket (`invoice.blade.php`), dan Shell Layout (`booking-shell.blade.php`).
- **Total Cacat Terverifikasi**: 20 Cacat (2 P0, 5 P1, 10 P2, 3 P3).

---

### 1. Cacat Prioritas P0 (Kritis / Fatal)

#### BUG-T3-01: Seluruh Rute Form Submission & Navigasi Rusak Total pada Custom Domain
- **Lokasi Berkas Terdampak**:
  - [`resources/views/customer/layouts/booking-shell.blade.php:78`](file:///c:/laragon/www/bookqu/resources/views/customer/layouts/booking-shell.blade.php#L78)
  - [`resources/views/customer/booking/program-selection.blade.php:13`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/program-selection.blade.php#L13)
  - [`resources/views/customer/booking/date-selection.blade.php:5,25`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/date-selection.blade.php#L5)
  - [`resources/views/customer/booking/time-selection.blade.php:5,25,142`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/time-selection.blade.php#L5)
  - [`resources/views/customer/booking/checkout.blade.php:5,59`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/checkout.blade.php#L5)
  - [`resources/views/customer/booking/payment.blade.php:5`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/payment.blade.php#L5)
  - [`resources/views/customer/partials/payment/cancel-modal.blade.php:66`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/cancel-modal.blade.php#L66)
  - [`resources/views/customer/partials/payment/scripts.blade.php:5,6`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/scripts.blade.php#L5)
  - [`resources/views/customer/booking/invoice.blade.php:5,335,345`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/invoice.blade.php#L5)
  - [`resources/views/customer/partials/payment/failed-state.blade.php:35,42`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/failed-state.blade.php#L35)
  - [`resources/views/customer/partials/payment/expired-state.blade.php:26`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/expired-state.blade.php#L26)
  - [`resources/views/customer/manage/payment-show.blade.php:107`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-show.blade.php#L107)
  - [`resources/views/customer/manage/reschedule.blade.php:19`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/reschedule.blade.php#L19)
  - [`resources/views/customer/partials/manage/header.blade.php:5,31`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/manage/header.blade.php#L5)
- **Deskripsi & Dampak**:
  Rute custom domain di `routes/web.php:293` didefinisikan dengan parameter domain: `Route::domain('{custom_domain}')`. Ketika view memanggil `route(CustomerBookingRoutes::name(...), $tenant->slug)`, argumen pertama mengikat `{custom_domain}`, menghasilkan URL salah:
  `http://studio-brama/booking/select-program`
  Peramban gagal melakukan resolusi DNS (`DNS_PROBE_FINISHED_NXDOMAIN`). **Seluruh navigasi, submit formulir, tombol kembali, polling status, dan pembatalan di custom domain lumpuh 100%**.
- **Akar Masalah**:
  Helper `CustomerBookingRoutes::url(...)` telah dibuat khusus untuk mengatasi parameter binding ini, tetapi template Blade keliru menggunakan `route(CustomerBookingRoutes::name(...))`.
- **Rekomendasi Perbaikan**:
  Ganti seluruh pemanggilan di 14 berkas Blade tersebut dengan:
  ```blade
  \App\Support\CustomerBookingRoutes::url('nama.rute', [$tenant->slug, ...])
  ```

---

#### BUG-T3-02: JavaScript TDZ ReferenceError Mematikan Seluruh Interaksi Halaman Pembayaran
- **Lokasi Berkas**: [`resources/views/customer/partials/payment/scripts.blade.php:275-330`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/scripts.blade.php#L275-L330)
- **Deskripsi & Dampak**:
  Pada `DOMContentLoaded`, fungsi `updateCountdown()` dipanggil pada baris 318 sebelum deklarasi `const timer = setInterval(...)` pada baris 319. Di dalam `updateCountdown()`, baris 279 memeriksa `if (timer) clearInterval(timer)`. Jika sisa detik <= 0 saat halaman dimuat, peramban melempar:
  `Uncaught ReferenceError: Cannot access 'timer' before initialization`.
  **Eksekusi script mati seketika**. Listener tombol "Bayar Sekarang" (baris 324) dan tombol "Periksa Status" (baris 328) tidak pernah terpasang, membuat halaman membeku total.
- **Rekomendasi Perbaikan**:
  Pindahkan deklarasi variabel ke awal fungsi:
  ```javascript
  let timer = null;
  let remainingSeconds = {{ max(0, $payment->expired_at ? (int) now()->diffInSeconds($payment->expired_at, false) : 900) }};
  ```

---

### 2. Cacat Prioritas P1 (Tinggi / Broken Functionality)

#### BUG-T3-03: Hilangnya Modifier Classes BEM pada Slot Kartu Waktu Step 3
- **Lokasi Berkas**: [`resources/views/components/customer/time-slot-card.blade.php:3-10`](file:///c:/laragon/www/bookqu/resources/views/components/customer/time-slot-card.blade.php#L3-L10)
- **Deskripsi**: File CSS `booking-program.css` mendefinisikan `.booking-time-slot:not(.booking-time-slot--disabled):hover` dan `.booking-time-slot--selected`. Namun di komponen Blade, atribut `:class` tidak menyertakan nama modifier class tersebut. Akibatnya, slot yang sudah penuh (`disabled`) tetap bergerak melayang saat di-hover dan bisa di-klik, memperdaya calon pelanggan.
- **Rekomendasi Perbaikan**:
  Sisipkan class BEM ke binding `:class`:
  ```blade
  'booking-time-slot--selected': {{ $slotVar }}.isSelected,
  'booking-time-slot--disabled': {{ $slotVar }}.isDisabled
  ```

#### BUG-T3-04: Ketiadaan Nominal Total Harga pada Mobile Bottom Bar Step 3
- **Lokasi Berkas**: [`resources/views/customer/booking/time-selection.blade.php:267-293`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/time-selection.blade.php#L267-L293)
- **Deskripsi**: Di layar desktop, total harga tampil jelas di ringkasan samping. Pada layar ponsel (< 1024px), ringkasan samping disembunyikan dan diganti oleh bilah bawah melayang (`.booking-mobile-bar`). Namun bilah ini hanya menampilkan jumlah slot tanpa nominal Rupiah total harga. Pelanggan di HP tidak tahu berapa total biaya sebelum menekan lanjut.
- **Rekomendasi Perbaikan**: Tambahkan `<span class="text-sm font-extrabold text-[#4F46E5]" x-text="totalLabel"></span>` pada `.booking-mobile-bar`.

#### BUG-T3-05: Teks Tautan Manajemen Invoice Menghilangkan Token Rahasia (Memicu 403 Forbidden)
- **Lokasi Berkas**: [`resources/views/customer/booking/invoice.blade.php:244-252`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/invoice.blade.php#L244-L252)
- **Deskripsi**: Teks URL yang ditampilkan di dalam `<span>` sengaja membuang parameter `?token=...` demi kerapian visual. Pelanggan yang menyalin teks tautan dan menempelkannya di browser lain menerima pesan penolakan `403 Forbidden: Token tidak valid`.
- **Rekomendasi Perbaikan**: Tampilkan teks URL lengkap yang memuat token dan sediakan tombol salin tautan terdedikasi.

#### BUG-T3-06: Pembekuan Tombol Submit Checkout Akibat BFCache Navigasi Kembali
- **Lokasi Berkas**: [`resources/views/customer/booking/checkout.blade.php:128-136`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/checkout.blade.php#L128-L136)
- **Deskripsi**: Saat form data diri disubmit, tombol dikunci `disabled = true`. Jika pelanggan menekan tombol *Back* dari halaman pembayaran, browser Back-Forward Cache (BFCache) me-restore state tombol tetap terkunci dan abu-abu. Form tidak bisa disubmit lagi tanpa reload manual.
- **Rekomendasi Perbaikan**: Tangani event `window.addEventListener('pageshow', ...)` untuk mengembalikan `disabled = false`.

#### BUG-T3-07: Format Tanggal Step 3 Menampilkan Bahasa Inggris Mentah Mengabaikan Kamus Lokalisasi
- **Lokasi Berkas**: [`app/Http/Controllers/Customer/BookingController.php:358`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Customer/BookingController.php#L358)
- **Deskripsi**: Tanggal terpilih di-generate oleh controller menggunakan format PHP mentah `'l, F jS'` ("Monday, October 19th"), membypass kamus bahasa Indonesia yang ada di modul JavaScript.
- **Rekomendasi Perbaikan**:
  Ganti menjadi:
  ```php
  $selectedDateLabel = Carbon::parse($selectedDate)->locale('id')->translatedFormat('l, d F Y');
  ```

---

### 3. Cacat Prioritas P2 & P3 (Sedang & Rendah)

- **BUG-T3-08 [P2]**: Filter kategori berlayanan kosong menampilkan halaman putih tanpa empty state ([program-selection.blade.php#L239](file:///c:/laragon/www/bookqu/resources/views/customer/booking/program-selection.blade.php#L239)).
- **BUG-T3-09 [P2]**: Touch target tombol header mobile ("Kembali" & "Bantuan") hanya 28px, melanggar standar WCAG 2.1 (minimal 44px).
- **BUG-T3-10 [P2]**: Data nama dan nomor telepon pemesan terhapus saat terjadi tabrakan pemesanan slot jadwal (slot collision).
- **BUG-T3-11 [P2]**: Mode simulasi pengujian cepat (`?simulate=1`) gagal di Step 4 karena mencoba mencari jadwal dummy di database.
- **BUG-T3-12 [P2]**: Pemilihan tanggal kalender pada layar mobile 360px mengalami teks terpotong ("12..." alih-alih "12 slot").
- **BUG-T3-13 [P2]**: Tautan Google Calendar di halaman invoice tidak menyertakan parameter `&ctz=Asia/Jakarta` sehingga berisiko meleset 1-2 jam di luar WIB.
- **BUG-T3-14 [P2]**: Tombol kembali header di Step 5 membiarkan slot terkunci 15 menit tanpa membatalkan order.
- **BUG-T3-15 [P2]**: Pengecualian domain utama di `TenantMiddleware.php` melewatkan domain produksi `bookqu.my.id`.
- **BUG-T3-16 [P2]**: Ejeksi sesi checkout yang kedaluwarsa terjadi tanpa menampilkan flash error informatif ke pelanggan.
- **BUG-T3-17 [P2]**: Karakter kutip pada nama layanan di Step 1 memicu error parser HTML Alpine.js.
- **BUG-T3-18 [P3]**: Tombol "Pesan Sesi Baru" dan "Kembali ke Beranda" diletakkan berdampingan dengan URL target identik.
- **BUG-T3-19 [P3]**: Inkonsistensi validasi panjang nama pemesan: client 3 karakter, backend menerima 1 karakter.
- **BUG-T3-20 [P3]**: Ketiadaan fallback penanganan Clipboard API pada browser non-HTTPS.
