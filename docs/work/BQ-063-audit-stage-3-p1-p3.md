---
id: BQ-063
title: Audit stage 3 P1-P3 defects (Customer Booking Journey: Storefront & 6-Langkah Reservasi hingga Checkout)
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: booking
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-3-CUSTOMER-BOOKING-JOURNEY.md at a68ffe8
---

# BQ-063: Audit stage 3 — P1, P2 and P3 defects

Migrated verbatim from TAHAP-3-CUSTOMER-BOOKING-JOURNEY.md (Customer Booking Journey: Storefront & 6-Langkah Reservasi hingga Checkout). Status in the current code is unverified (BQ-039). P1 defects that touch a core flow are launch-relevant; promote any of them to its own item when work starts.

## P1 checklist

- [ ] BUG-T3-03: Hilangnya Modifier Classes BEM pada Slot Kartu Waktu Step 3
- [ ] BUG-T3-04: Ketiadaan Nominal Total Harga pada Mobile Bottom Bar Step 3
- [ ] BUG-T3-05: Teks Tautan Manajemen Invoice Menghilangkan Token Rahasia (Memicu 403 Forbidden)
- [ ] BUG-T3-06: Pembekuan Tombol Submit Checkout Akibat BFCache Navigasi Kembali
- [ ] BUG-T3-07: Format Tanggal Step 3 Menampilkan Bahasa Inggris Mentah Mengabaikan Kamus Lokalisasi

## P2 / P3 checklist

- [ ] **BUG-T3-08 [P2]**: Filter kategori berlayanan kosong menampilkan halaman putih tanpa empty state ([program-selection.blade.php#L239](file:///c:/laragon/www/bookqu/resources/views/customer/booking/program-selection.blade.php#L239)).
- [ ] **BUG-T3-09 [P2]**: Touch target tombol header mobile ("Kembali" & "Bantuan") hanya 28px, melanggar standar WCAG 2.1 (minimal 44px).
- [ ] **BUG-T3-10 [P2]**: Data nama dan nomor telepon pemesan terhapus saat terjadi tabrakan pemesanan slot jadwal (slot collision).
- [ ] **BUG-T3-11 [P2]**: Mode simulasi pengujian cepat (`?simulate=1`) gagal di Step 4 karena mencoba mencari jadwal dummy di database.
- [ ] **BUG-T3-12 [P2]**: Pemilihan tanggal kalender pada layar mobile 360px mengalami teks terpotong ("12..." alih-alih "12 slot").
- [ ] **BUG-T3-13 [P2]**: Tautan Google Calendar di halaman invoice tidak menyertakan parameter `&ctz=Asia/Jakarta` sehingga berisiko meleset 1-2 jam di luar WIB.
- [ ] **BUG-T3-14 [P2]**: Tombol kembali header di Step 5 membiarkan slot terkunci 15 menit tanpa membatalkan order.
- [ ] **BUG-T3-15 [P2]**: Pengecualian domain utama di `TenantMiddleware.php` melewatkan domain produksi `bookqu.my.id`.
- [ ] **BUG-T3-16 [P2]**: Ejeksi sesi checkout yang kedaluwarsa terjadi tanpa menampilkan flash error informatif ke pelanggan.
- [ ] **BUG-T3-17 [P2]**: Karakter kutip pada nama layanan di Step 1 memicu error parser HTML Alpine.js.
- [ ] **BUG-T3-18 [P3]**: Tombol "Pesan Sesi Baru" dan "Kembali ke Beranda" diletakkan berdampingan dengan URL target identik.
- [ ] **BUG-T3-19 [P3]**: Inkonsistensi validasi panjang nama pemesan: client 3 karakter, backend menerima 1 karakter.
- [ ] **BUG-T3-20 [P3]**: Ketiadaan fallback penanganan Clipboard API pada browser non-HTTPS.

## P1 details

### BUG-T3-03: Hilangnya Modifier Classes BEM pada Slot Kartu Waktu Step 3

- **Lokasi Berkas**: [`resources/views/components/customer/time-slot-card.blade.php:3-10`](file:///c:/laragon/www/bookqu/resources/views/components/customer/time-slot-card.blade.php#L3-L10)
- **Deskripsi**: File CSS `booking-program.css` mendefinisikan `.booking-time-slot:not(.booking-time-slot--disabled):hover` dan `.booking-time-slot--selected`. Namun di komponen Blade, atribut `:class` tidak menyertakan nama modifier class tersebut. Akibatnya, slot yang sudah penuh (`disabled`) tetap bergerak melayang saat di-hover dan bisa di-klik, memperdaya calon pelanggan.
- **Rekomendasi Perbaikan**:
  Sisipkan class BEM ke binding `:class`:
  ```blade
  'booking-time-slot--selected': {{ $slotVar }}.isSelected,
  'booking-time-slot--disabled': {{ $slotVar }}.isDisabled
  ```

### BUG-T3-04: Ketiadaan Nominal Total Harga pada Mobile Bottom Bar Step 3

- **Lokasi Berkas**: [`resources/views/customer/booking/time-selection.blade.php:267-293`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/time-selection.blade.php#L267-L293)
- **Deskripsi**: Di layar desktop, total harga tampil jelas di ringkasan samping. Pada layar ponsel (< 1024px), ringkasan samping disembunyikan dan diganti oleh bilah bawah melayang (`.booking-mobile-bar`). Namun bilah ini hanya menampilkan jumlah slot tanpa nominal Rupiah total harga. Pelanggan di HP tidak tahu berapa total biaya sebelum menekan lanjut.
- **Rekomendasi Perbaikan**: Tambahkan `<span class="text-sm font-extrabold text-[#4F46E5]" x-text="totalLabel"></span>` pada `.booking-mobile-bar`.

### BUG-T3-05: Teks Tautan Manajemen Invoice Menghilangkan Token Rahasia (Memicu 403 Forbidden)

- **Lokasi Berkas**: [`resources/views/customer/booking/invoice.blade.php:244-252`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/invoice.blade.php#L244-L252)
- **Deskripsi**: Teks URL yang ditampilkan di dalam `<span>` sengaja membuang parameter `?token=...` demi kerapian visual. Pelanggan yang menyalin teks tautan dan menempelkannya di browser lain menerima pesan penolakan `403 Forbidden: Token tidak valid`.
- **Rekomendasi Perbaikan**: Tampilkan teks URL lengkap yang memuat token dan sediakan tombol salin tautan terdedikasi.

### BUG-T3-06: Pembekuan Tombol Submit Checkout Akibat BFCache Navigasi Kembali

- **Lokasi Berkas**: [`resources/views/customer/booking/checkout.blade.php:128-136`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/checkout.blade.php#L128-L136)
- **Deskripsi**: Saat form data diri disubmit, tombol dikunci `disabled = true`. Jika pelanggan menekan tombol *Back* dari halaman pembayaran, browser Back-Forward Cache (BFCache) me-restore state tombol tetap terkunci dan abu-abu. Form tidak bisa disubmit lagi tanpa reload manual.
- **Rekomendasi Perbaikan**: Tangani event `window.addEventListener('pageshow', ...)` untuk mengembalikan `disabled = false`.

### BUG-T3-07: Format Tanggal Step 3 Menampilkan Bahasa Inggris Mentah Mengabaikan Kamus Lokalisasi

- **Lokasi Berkas**: [`app/Http/Controllers/Customer/BookingController.php:358`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Customer/BookingController.php#L358)
- **Deskripsi**: Tanggal terpilih di-generate oleh controller menggunakan format PHP mentah `'l, F jS'` ("Monday, October 19th"), membypass kamus bahasa Indonesia yang ada di modul JavaScript.
- **Rekomendasi Perbaikan**:
  Ganti menjadi:
  ```php
  $selectedDateLabel = Carbon::parse($selectedDate)->locale('id')->translatedFormat('l, d F Y');
  ```

## Evidence

_Fill in when done._
