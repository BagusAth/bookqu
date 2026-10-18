# Dokumentasi Audit Cacat: Tahap 4
## Payment Gateway, Webhooks, Halaman Pembayaran & Expiry Engine

- **Lingkup**: Unified Payment Gateway (`SingaPayPaymentGateway.php`, `MidtransPaymentGateway.php`), Webhooks (`SingaPayWebhookController.php`, `MidtransWebhookController.php`), Layar Pembayaran (`payment.blade.php`, partials), Invoice (`invoice.blade.php`), Background Engine (`ExpireBookingPayments.php`, `CheckPaymentStatus.php`).
- **Total Cacat Terverifikasi**: 16 Cacat (3 P0, 6 P1, 5 P2, 2 P3).

---

### 1. Cacat Prioritas P0 (Kritis / Fatal)

#### BUG-T4-01: Pembatalan Booking Tanpa Membatalkan Transaksi Gateway (Zombie Debits)
- **Lokasi Berkas**: 
  - [`app/Http/Controllers/Customer/BookingController.php:803-838`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Customer/BookingController.php#L803-L838)
  - [`app/Actions/Payment/SynchronizePaymentStatus.php:115-127`](file:///c:/laragon/www/bookqu/app/Actions/Payment/SynchronizePaymentStatus.php#L115-L127)
- **Deskripsi & Dampak**:
  Ketika pelanggan menekan tombol *"Batalkan & Ganti Jadwal"*, sistem mengupdate status pembayaran di database lokal menjadi `'gagal'` dan membatalkan reservasi. Namun sistem **tidak pernah memanggil API cancel** ke Midtrans (`/v2/{order_id}/cancel`) atau menonaktifkan link SingaPay. Jika pelanggan kemudian membayar QRIS/VA yang sudah terlanjur dibuka melalui aplikasi perbankan mereka, webhook BookQu menolaknya sebagai transaksi gagal. **Uang pelanggan terpotong tetapi tidak ada booking yang tercipta dan tidak ada refund otomatis yang dipicu**.
- **Rekomendasi Perbaikan**:
  Panggil gateway cancel sebelum mengubah status lokal menjadi gagal:
  ```php
  $gateway = $this->gatewayManager->forPayment($payment);
  $gateway->cancelTransaction($payment);
  ```

---

#### BUG-T4-02: Pengiriman Email Sinkronik Memperlambat Webhook dan Menimbulkan Pantulan Invoice Prematur
- **Lokasi Berkas**: 
  - [`app/Actions/Payment/SynchronizePaymentStatus.php:203-238`](file:///c:/laragon/www/bookqu/app/Actions/Payment/SynchronizePaymentStatus.php#L203-L238)
  - [`app/Mail/BookingGroupInvoiceMail.php:15`](file:///c:/laragon/www/bookqu/app/Mail/BookingGroupInvoiceMail.php#L15)
  - [`app/Notifications/NewBookingOwnerNotification.php:10`](file:///c:/laragon/www/bookqu/app/Notifications/NewBookingOwnerNotification.php#L10)
- **Deskripsi & Dampak**:
  Kedua mailable email tidak mengimplementasikan interface `ShouldQueue`. Di dalam callback transaksi webhook, server melakukan koneksi SMTP sinkron untuk mengirim 2 email yang memakan waktu 2–4 detik. Pelanggan yang selesai membayar di SingaPay hosted checkout langsung di-redirect ke `/invoice` dalam ~500ms. Karena webhook belum selesai, status di DB masih `pending`. Halaman `showInvoice` menolak status pending dan melempar pelanggan kembali ke layar checkout dengan pesan error membingungkan: *"Silakan selesaikan pembayaran terlebih dahulu"*.
- **Rekomendasi Perbaikan**:
  1. Tambahkan `implements ShouldQueue` pada `BookingGroupInvoiceMail` dan `NewBookingOwnerNotification`.
  2. Pada `BookingController@showInvoice`, jika status masih `pending`, lakukan `refresh()` dan toleransi grace period sebelum me-redirect error.

---

#### BUG-T4-03: Polling Status Mengalami Runaway Loop Tanpa Exponential Backoff
- **Lokasi Berkas**: [`resources/views/customer/partials/payment/scripts.blade.php:183-261`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/scripts.blade.php#L183-L261)
- **Deskripsi & Dampak**:
  Fungsi auto-polling status berjalan setiap 2,5 detik tanpa batas percobaan maksimal (*max retries*) dan tanpa penanganan status error HTTP 419/500. Jika koneksi bermasalah atau sesi kedaluwarsa, script terus menembak server ribuan kali tanpa henti (menciptakan DoS lokal ke server web).
- **Rekomendasi Perbaikan**: Batasi polling maksimal 120 iterasi (5 menit) dan hentikan jika terjadi 3 kegagalan HTTP berturut-turut.

---

### 2. Cacat Prioritas P1 (Tinggi / Broken Functionality)

#### BUG-T4-04: Cek Status Transaksi SingaPay Hanyalah Pembacaan Database Lokal (No-Op Poll)
- **Lokasi Berkas**: [`app/Infrastructure/Payments/SingaPay/SingaPayPaymentGateway.php:272-301`](file:///c:/laragon/www/bookqu/app/Infrastructure/Payments/SingaPay/SingaPayPaymentGateway.php#L272-L301)
- **Deskripsi**: Method `checkTransactionStatus` untuk driver SingaPay hanya melakukan `$payment->refresh()` dan memeriksa record lokal. Tidak ada pemanggilan API ke gateway SingaPay. Jika webhook terlambat atau terhalang firewall, tombol "Periksa Status Pembayaran" yang ditekan berkali-kali oleh pengguna sama sekali tidak melakukan apa-apa.
- **Rekomendasi Perbaikan**: Implementasikan query status ke endpoint status/history SingaPay jika webhook belum diterima.

#### BUG-T4-05: Tombol "Coba Bayar Lagi" Hilang Permanen pada Gateway SingaPay
- **Lokasi Berkas**: [`resources/views/customer/partials/payment/failed-state.blade.php:25-33`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/failed-state.blade.php#L25-L33)
- **Deskripsi**: Tombol render dibatasi oleh `@if(!empty($payment->snap_token))`. Pada gateway SingaPay yang menggunakan `payment_url`, tombol retry tidak pernah tampil saat pembayaran dinyatakan gagal.
- **Rekomendasi Perbaikan**:
  Ubah kondisi menjadi:
  ```blade
  @if(!empty($payment->snap_token) || !empty($payment->payment_url))
  ```

#### BUG-T4-06: Dokumen Invoice Mencetak Elemen Navigasi, Progress Bar, dan Tombol ke Kertas Fisik
- **Lokasi Berkas**: 
  - [`resources/views/customer/booking/invoice.blade.php:261,322`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/invoice.blade.php#L261)
  - [`resources/views/customer/layouts/booking-shell.blade.php:9-12`](file:///c:/laragon/www/bookqu/resources/views/customer/layouts/booking-shell.blade.php#L9-L12)
- **Deskripsi**: Class `.no-print` yang dipasang pada tombol-tombol dan header tidak pernah didefinisikan di CSS. Saat pengguna mengklik "Cetak Invoice" atau menekan Ctrl+P, seluruh bilah atas, 6-langkah progress bar, animasi titik confetti, dan tombol "Pesan Sesi Baru" tercetak mengotori lembar invoice fisik.
- **Rekomendasi Perbaikan**:
  Tambahkan blok CSS `@media print` yang menyembunyikan header, footer, tombol, dan progress bar.

#### BUG-T4-07: Ketidaksesuaian Grace Period Slot Lockout: Terkunci 15 Menit Meski Pembayaran Batal
- **Lokasi Berkas**: 
  - [`app/Domain/Booking/BookingRules.php:83-91`](file:///c:/laragon/www/bookqu/app/Domain/Booking/BookingRules.php#L83-L91)
  - [`app/Actions/Booking/EvictStalePendingBookings.php:35-43`](file:///c:/laragon/www/bookqu/app/Actions/Booking/EvictStalePendingBookings.php#L35-L43)
- **Deskripsi**: Pengecekan keterisian slot meng-hardcode `created_at >= now()->subMinutes(15)` tanpa memeriksa `payments.expired_at` atau status `payments.status = 'gagal'`. Pengguna yang membatalkan pesanan di menit ke-5 tidak bisa memesan ulang jadwal yang sama hingga menit ke-15 terlampaui.
- **Rekomendasi Perbaikan**: Sertakan relasi payment: jika payment berstatus gagal atau `expired_at < now()`, bebaskan slot seketika.

#### BUG-T4-08: Countdown Timer Client Mencapai 00:00 Tanpa Mengabari Server
- **Lokasi Berkas**: [`resources/views/customer/partials/payment/scripts.blade.php:277-294`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/scripts.blade.php#L277-L294)
- **Deskripsi**: Ketika hitung mundur mencapai nol di browser, JavaScript hanya mengubah tampilan DOM menjadi kadaluarsa, tanpa mengirim sinyal HTTP ke server untuk menandai pembayaran kedaluwarsa di database.

#### BUG-T4-09: Ketiadaan Tombol Lanjutkan Pembayaran pada Portal Pelanggan
- **Lokasi Berkas**: [`resources/views/customer/manage/payment-show.blade.php:137,288`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-show.blade.php#L137)
- **Deskripsi**: Pelanggan yang mengakses portal reservasi saat status masih pending tidak menemukan tombol CTA "Lanjutkan Pembayaran", menghentikan alur transaksi.

---

### 3. Cacat Prioritas P2 & P3 (Sedang & Rendah)

- **BUG-T4-10 [P2]**: Midtrans gateway keliru membaca konfigurasi SingaPay `config('payment.drivers.singapay.expiry_minutes')` ([MidtransPaymentGateway.php:315](file:///c:/laragon/www/bookqu/app/Infrastructure/Payments/Midtrans/MidtransPaymentGateway.php#L315)).
- **BUG-T4-11 [P2]**: Tombol "Periksa Status Pembayaran" gagal hening (silent fail) saat menerima respons HTTP 403 atau 500 tanpa memperbarui antarmuka pengguna.
- **BUG-T4-12 [P2]**: Variabel `$cacheCleared` pada artisan command `ExpireBookingPayments` tidak pernah diisi di dalam loop sehingga selalu melaporkan "0 slots cleared".
- **BUG-T4-13 [P2]**: Tombol navigasi "Buat Reservasi Baru" dan "Kembali ke Pemesanan" pada layar gagal mengarah ke target yang sama.
- **BUG-T4-14 [P2]**: Ketiadaan penanganan penutupan pop-up Snap Midtrans saat pengguna mengklik tanda silang tanpa menyelesaikan pembayaran.
- **BUG-T4-15 [P3]**: Format waktu kedaluwarsa tidak menyertakan penanda zona waktu WIB/WITA/WIT.
- **BUG-T4-16 [P3]**: Ikon salin nomor virtual account tidak memberikan transisi animasi centang hijau saat berhasil disalin.
