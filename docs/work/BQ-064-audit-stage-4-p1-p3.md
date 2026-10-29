---
id: BQ-064
title: Audit stage 4 P1-P3 defects (Payment Gateway, Webhooks, Halaman Pembayaran & Expiry Engine)
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: payment
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-4-PAYMENT-GATEWAY-EXPIRY.md at a68ffe8
---

# BQ-064: Audit stage 4 — P1, P2 and P3 defects

Migrated verbatim from TAHAP-4-PAYMENT-GATEWAY-EXPIRY.md (Payment Gateway, Webhooks, Halaman Pembayaran & Expiry Engine). Status in the current code is unverified (BQ-039). P1 defects that touch a core flow are launch-relevant; promote any of them to its own item when work starts.

> BUG-T4-04 overlaps BQ-003; BUG-T4-07 overlaps BQ-012.

## P1 checklist

- [ ] BUG-T4-04: Cek Status Transaksi SingaPay Hanyalah Pembacaan Database Lokal (No-Op Poll)
- [ ] BUG-T4-05: Tombol "Coba Bayar Lagi" Hilang Permanen pada Gateway SingaPay
- [ ] BUG-T4-06: Dokumen Invoice Mencetak Elemen Navigasi, Progress Bar, dan Tombol ke Kertas Fisik
- [ ] BUG-T4-07: Ketidaksesuaian Grace Period Slot Lockout: Terkunci 15 Menit Meski Pembayaran Batal
- [ ] BUG-T4-08: Countdown Timer Client Mencapai 00:00 Tanpa Mengabari Server
- [ ] BUG-T4-09: Ketiadaan Tombol Lanjutkan Pembayaran pada Portal Pelanggan

## P2 / P3 checklist

- [ ] **BUG-T4-10 [P2]**: Midtrans gateway keliru membaca konfigurasi SingaPay `config('payment.drivers.singapay.expiry_minutes')` ([MidtransPaymentGateway.php:315](file:///c:/laragon/www/bookqu/app/Infrastructure/Payments/Midtrans/MidtransPaymentGateway.php#L315)).
- [ ] **BUG-T4-11 [P2]**: Tombol "Periksa Status Pembayaran" gagal hening (silent fail) saat menerima respons HTTP 403 atau 500 tanpa memperbarui antarmuka pengguna.
- [ ] **BUG-T4-12 [P2]**: Variabel `$cacheCleared` pada artisan command `ExpireBookingPayments` tidak pernah diisi di dalam loop sehingga selalu melaporkan "0 slots cleared".
- [ ] **BUG-T4-13 [P2]**: Tombol navigasi "Buat Reservasi Baru" dan "Kembali ke Pemesanan" pada layar gagal mengarah ke target yang sama.
- [ ] **BUG-T4-14 [P2]**: Ketiadaan penanganan penutupan pop-up Snap Midtrans saat pengguna mengklik tanda silang tanpa menyelesaikan pembayaran.
- [ ] **BUG-T4-15 [P3]**: Format waktu kedaluwarsa tidak menyertakan penanda zona waktu WIB/WITA/WIT.
- [ ] **BUG-T4-16 [P3]**: Ikon salin nomor virtual account tidak memberikan transisi animasi centang hijau saat berhasil disalin.

## P1 details

### BUG-T4-04: Cek Status Transaksi SingaPay Hanyalah Pembacaan Database Lokal (No-Op Poll)

- **Lokasi Berkas**: [`app/Infrastructure/Payments/SingaPay/SingaPayPaymentGateway.php:272-301`](file:///c:/laragon/www/bookqu/app/Infrastructure/Payments/SingaPay/SingaPayPaymentGateway.php#L272-L301)
- **Deskripsi**: Method `checkTransactionStatus` untuk driver SingaPay hanya melakukan `$payment->refresh()` dan memeriksa record lokal. Tidak ada pemanggilan API ke gateway SingaPay. Jika webhook terlambat atau terhalang firewall, tombol "Periksa Status Pembayaran" yang ditekan berkali-kali oleh pengguna sama sekali tidak melakukan apa-apa.
- **Rekomendasi Perbaikan**: Implementasikan query status ke endpoint status/history SingaPay jika webhook belum diterima.

### BUG-T4-05: Tombol "Coba Bayar Lagi" Hilang Permanen pada Gateway SingaPay

- **Lokasi Berkas**: [`resources/views/customer/partials/payment/failed-state.blade.php:25-33`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/failed-state.blade.php#L25-L33)
- **Deskripsi**: Tombol render dibatasi oleh `@if(!empty($payment->snap_token))`. Pada gateway SingaPay yang menggunakan `payment_url`, tombol retry tidak pernah tampil saat pembayaran dinyatakan gagal.
- **Rekomendasi Perbaikan**:
  Ubah kondisi menjadi:
  ```blade
  @if(!empty($payment->snap_token) || !empty($payment->payment_url))
  ```

### BUG-T4-06: Dokumen Invoice Mencetak Elemen Navigasi, Progress Bar, dan Tombol ke Kertas Fisik

- **Lokasi Berkas**: 
  - [`resources/views/customer/booking/invoice.blade.php:261,322`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/invoice.blade.php#L261)
  - [`resources/views/customer/layouts/booking-shell.blade.php:9-12`](file:///c:/laragon/www/bookqu/resources/views/customer/layouts/booking-shell.blade.php#L9-L12)
- **Deskripsi**: Class `.no-print` yang dipasang pada tombol-tombol dan header tidak pernah didefinisikan di CSS. Saat pengguna mengklik "Cetak Invoice" atau menekan Ctrl+P, seluruh bilah atas, 6-langkah progress bar, animasi titik confetti, dan tombol "Pesan Sesi Baru" tercetak mengotori lembar invoice fisik.
- **Rekomendasi Perbaikan**:
  Tambahkan blok CSS `@media print` yang menyembunyikan header, footer, tombol, dan progress bar.

### BUG-T4-07: Ketidaksesuaian Grace Period Slot Lockout: Terkunci 15 Menit Meski Pembayaran Batal

- **Lokasi Berkas**: 
  - [`app/Domain/Booking/BookingRules.php:83-91`](file:///c:/laragon/www/bookqu/app/Domain/Booking/BookingRules.php#L83-L91)
  - [`app/Actions/Booking/EvictStalePendingBookings.php:35-43`](file:///c:/laragon/www/bookqu/app/Actions/Booking/EvictStalePendingBookings.php#L35-L43)
- **Deskripsi**: Pengecekan keterisian slot meng-hardcode `created_at >= now()->subMinutes(15)` tanpa memeriksa `payments.expired_at` atau status `payments.status = 'gagal'`. Pengguna yang membatalkan pesanan di menit ke-5 tidak bisa memesan ulang jadwal yang sama hingga menit ke-15 terlampaui.
- **Rekomendasi Perbaikan**: Sertakan relasi payment: jika payment berstatus gagal atau `expired_at < now()`, bebaskan slot seketika.

### BUG-T4-08: Countdown Timer Client Mencapai 00:00 Tanpa Mengabari Server

- **Lokasi Berkas**: [`resources/views/customer/partials/payment/scripts.blade.php:277-294`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/scripts.blade.php#L277-L294)
- **Deskripsi**: Ketika hitung mundur mencapai nol di browser, JavaScript hanya mengubah tampilan DOM menjadi kadaluarsa, tanpa mengirim sinyal HTTP ke server untuk menandai pembayaran kedaluwarsa di database.

### BUG-T4-09: Ketiadaan Tombol Lanjutkan Pembayaran pada Portal Pelanggan

- **Lokasi Berkas**: [`resources/views/customer/manage/payment-show.blade.php:137,288`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-show.blade.php#L137)
- **Deskripsi**: Pelanggan yang mengakses portal reservasi saat status masih pending tidak menemukan tombol CTA "Lanjutkan Pembayaran", menghentikan alur transaksi.

## Evidence

_Fill in when done._
