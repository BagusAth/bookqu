# Dokumentasi Audit Cacat: Tahap 5
## Customer Self-Service (Tanpa Akun) & Owner Booking Operations

- **Lingkup**: Portal Manajemen Pembayaran Grup (`payment-show.blade.php`, `payment-invoice.blade.php`), Single-Booking Legacy (`show.blade.php`, `reschedule.blade.php`, `invoice.blade.php`), partials (`ticket-card.blade.php`, `cancel-modal.blade.php`, `review-section.blade.php`), Owner Bookings Dashboard (`bookings.blade.php`, partials), Modal Reschedule Owner (`modal-reschedule-booking.blade.php`), Walk-in Engine (`CreateWalkInBooking.php`, `calendar/detail-modal.blade.php`).
- **Total Cacat Terverifikasi**: 30 Cacat (6 P0, 10 P1, 10 P2, 4 P3).

---

### 1. Cacat Prioritas P0 (Kritis / Fatal)

#### BUG-T5-01: Kebocoran Token Rahasia pada Google Calendar & Teks Berbagi WhatsApp
- **Lokasi Berkas**: 
  - [`resources/views/customer/manage/show.blade.php:25-42`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/show.blade.php#L25-L42)
  - [`resources/views/customer/manage/invoice.blade.php:428-433`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/invoice.blade.php#L428-L433)
- **Deskripsi & Dampak (Kerentanan Keamanan Serius)**:
  URL manajemen lengkap beserta query string token otentikasi rahasia `?token=...` dimasukkan ke dalam teks berbagi WhatsApp dan deskripsi Google Calendar publik. Siapapun yang menerima forward pesan WhatsApp atau melihat kalender bersama dapat membajak hak akses untuk membatalkan atau mengubah jadwal pelanggan tanpa izin.
- **Akar Masalah**:
  ```php
  $manageUrl = route('booking.manage', ['booking_code' => $booking->booking_code]) . ($token ? '?token=' . $token : '');
  $eventDetails = "...\nKelola Booking: " . $manageUrl;
  $waText = "...\nKelola Reservasi: " . $manageUrl;
  ```
- **Rekomendasi Perbaikan**:
  Hapus query string token dari tautan eksternal yang dibagikan secara publik.

---

#### BUG-T5-02: Fatal Token Scope Mismatch pada Aksi Reschedule & Batal di Portal Payment Group
- **Lokasi Berkas**: 
  - [`resources/views/customer/manage/payment-show.blade.php:295,434`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-show.blade.php#L295)
  - [`app/Http/Controllers/Customer/BookingManageController.php:176-187`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Customer/BookingManageController.php#L176-L187)
- **Deskripsi & Dampak**:
  Pada portal payment group, tautan reschedule dan cancel menggunakan `$token` (`Payment->manage_token`) sebagai fallback. Di `BookingManageController::resolveBooking`, scope `'cancel'` hanya menerima `cancellation_token` dan `'reschedule'` hanya menerima `reschedule_token`. Penggunaan `manage_token` ditolak keras dengan status `HTTP 403 Forbidden: Token tidak valid`. Pelanggan tidak dapat membatalkan atau mengubah jadwal mereka dari portal grup.
- **Rekomendasi Perbaikan**:
  Perbarui `resolveBooking` agar `manage_token` diizinkan untuk scope cancel dan reschedule jika mencakup transaksi tersebut.

---

#### BUG-T5-03: Mismatch Total 6 Atribut Payload `$bookingData` Merusak Modal Reschedule Owner
- **Lokasi Berkas**: 
  - [`resources/views/owner/partials/bookings/desktop-table.blade.php:25-50, 195`](file:///c:/laragon/www/bookqu/resources/views/owner/partials/bookings/desktop-table.blade.php#L25-L50)
  - [`resources/views/owner/partials/bookings/mobile-cards.blade.php:9-34`](file:///c:/laragon/www/bookqu/resources/views/owner/partials/bookings/mobile-cards.blade.php#L9-L34)
  - [`resources/views/components/owner/modal-reschedule-booking.blade.php:22-30`](file:///c:/laragon/www/bookqu/resources/views/components/owner/modal-reschedule-booking.blade.php#L22-L30)
- **Deskripsi & Dampak (Kerusakan Total Modal Owner Reschedule)**:
  Array `$bookingData` yang dikirim dari tabel booking tidak sinkron dengan properti JavaScript Alpine.js di modal reschedule:
  1. `'name'` vs `booking?.namapelanggan`: Nama pelanggan menjadi **KOSONG / BLANK**.
  2. `'code'` vs `booking?.booking_code`: Kode booking fallback ke ID numerik.
  3. `'time'` vs `booking?.jam`: Jam lama tampil sebagai teks cacat **" WIB"**.
  4. `'idschedule'` **absen total**: Slot lama tidak terdefinisi (`selectedScheduleId` selalu `null`).
  5. `'date'` ('d M Y') vs `booking?.tanggalbooking` ('Y-m-d'): Tanggal modal terpaksa reset ke hari ini, dan tanggal lama tampil sebagai **"-"**.
  6. `'service'` (string) vs `booking?.layanan?.namalayanan`: Layanan selalu fallback ke teks generik **"Layanan Utama"**.
- **Rekomendasi Perbaikan**:
  Standarisasi payload `$bookingData` di `desktop-table.blade.php` dan `mobile-cards.blade.php` agar menyediakan kedua format kunci.

---

#### BUG-T5-04: Input Tanggal pada Modal Reschedule Owner Tidak Memiliki Atribut `name="tanggal"`
- **Lokasi Berkas**: [`resources/views/components/owner/modal-reschedule-booking.blade.php:156-163`](file:///c:/laragon/www/bookqu/resources/views/components/owner/modal-reschedule-booking.blade.php#L156-L163)
- **Deskripsi & Dampak**:
  Elemen `<input type="date">` memiliki `x-model="selectedDate"` tetapi **tidak memiliki atribut `name="tanggal"`**. Karena form disubmit secara POST standar, tanggal baru yang dipilih owner tidak pernah dikirimkan ke server. Server menerima `tanggal = null` dan validasi tanggal diabaikan.
- **Rekomendasi Perbaikan**: Tambahkan atribut `name="tanggal"` pada tag input.

---

#### BUG-T5-05: Bahaya Fatal Crash HTTP 500 (`Attempt to read property on null`) pada Tabel Owner
- **Lokasi Berkas**: 
  - [`resources/views/owner/partials/bookings/desktop-table.blade.php:31,78,99`](file:///c:/laragon/www/bookqu/resources/views/owner/partials/bookings/desktop-table.blade.php#L31)
  - [`resources/views/owner/partials/bookings/mobile-cards.blade.php:15,81`](file:///c:/laragon/www/bookqu/resources/views/owner/partials/bookings/mobile-cards.blade.php#L15)
- **Deskripsi & Dampak**:
  Properti `$booking->layanan->namalayanan` dan `$booking->layanan->harga` diakses tanpa nullsafe operator `?->`. Pada PHP 8+, jika relasi layanan null (misal layanan terhapus), pemanggilan langsung memicu fatal error HTTP 500 dan melumpuhkan dashboard booking owner.
- **Rekomendasi Perbaikan**: Gunakan nullsafe operator: `$booking->layanan?->namalayanan` dan `$booking->layanan?->harga ?? 0`.

---

#### BUG-T5-06: Ketiadaan Total Komponen Walk-in Modal pada Halaman Bookings Management
- **Lokasi Berkas**: [`resources/views/owner/bookings.blade.php:15-20`](file:///c:/laragon/www/bookqu/resources/views/owner/bookings.blade.php#L15-L20)
- **Deskripsi & Dampak**:
  Meskipun action `CreateWalkInBooking` dan route backend telah tersedia, di halaman `/owner/bookings` sama sekali tidak ada tombol "+ Walk-in Booking" dan tidak ada komponen modal walk-in. Owner yang sedang melihat daftar booking tidak bisa mendaftarkan tamu on-site secara langsung tanpa berpindah ke kalender.
- **Rekomendasi Perbaikan**:
  Buat komponen mandiri `resources/views/components/owner/modal-walkin.blade.php` dengan AJAX slot picker dan pasang tombol trigger di header `owner/bookings.blade.php`.

---

### 2. Cacat Prioritas P1 (Tinggi / Broken Functionality)

#### BUG-T5-07: Slot Waktu Jam Lampau (Past Time) Tetap Muncul pada Modal Reschedule Owner
- **Lokasi Berkas**: 
  - [`app/Actions/Schedule/GetAvailableSchedules.php:230-260`](file:///c:/laragon/www/bookqu/app/Actions/Schedule/GetAvailableSchedules.php#L230-L260)
  - [`app/Actions/Booking/RescheduleBooking.php:107-110`](file:///c:/laragon/www/bookqu/app/Actions/Booking/RescheduleBooking.php#L107-L110)
- **Deskripsi**: Method `getSlotsForReschedule` tidak memfilter jam yang sudah lewat untuk hari ini. Owner dapat mengklik tombol "Pilih" pada slot jam 08:00 di siang hari, namun saat disubmit server menolak dengan exception `SLOT_NOT_FOUND`.
- **Rekomendasi Perbaikan**: Filter jam lampau jika tanggal yang dipilih sama dengan hari ini WIB.

#### BUG-T5-08: Customer Reschedule Mengabaikan Tanggal Blokir / Libur Merchant
- **Lokasi Berkas**: 
  - [`app/Http/Controllers/Customer/BookingManageController.php:303-326`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Customer/BookingManageController.php#L303-L326)
  - [`app/Actions/Booking/RescheduleBooking.php:86-120`](file:///c:/laragon/www/bookqu/app/Actions/Booking/RescheduleBooking.php#L86-L120)
- **Deskripsi**: Alur reschedule mandiri pelanggan tidak memeriksa tabel `owner_blocked_dates`, memungkinkan pelanggan memindahkan jadwal reservasi ke tanggal libur operasional toko merchant.
- **Rekomendasi Perbaikan**: Validasi tanggal terpilih terhadap `owner_blocked_dates`.

#### BUG-T5-09: Tombol Unduh Invoice Mengarah ke HTTP 404 pada Booking Pending / Cancelled
- **Lokasi Berkas**: [`resources/views/customer/partials/manage/ticket-card.blade.php:146-155`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/manage/ticket-card.blade.php#L146-L155)
- **Deskripsi**: Tombol invoice muncul pada seluruh status booking. Pada status pending atau cancelled, mengkliknya langsung membuka halaman 404 Not Found karena controller memvalidasi `status === 'sukses'`.
- **Rekomendasi Perbaikan**: Sembunyikan tombol invoice jika status bukan `paid` atau `completed`.

#### BUG-T5-10: Status Invoice Tercetak "LUNAS / SUKSES" untuk Reservasi yang Sudah Batal
- **Lokasi Berkas**: [`resources/views/customer/manage/payment-invoice.blade.php:58-60`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-invoice.blade.php#L58-L60)
- **Deskripsi**: Dokumen faktur payment group meng-hardcode badge "LUNAS / SUKSES". Transaksi yang seluruh sesinya telah dibatalkan atau direfund tetap tercetak sebagai dokumen berstatus lunas.
- **Rekomendasi Perbaikan**: Buat badge status dokumen dinamis mengevaluasi status agregat seluruh booking.

#### BUG-T5-11: Data Form Walk-In di Kalender Terhapus Bersih Saat Validasi Gagal (Data Loss)
- **Lokasi Berkas**: [`resources/views/owner/partials/calendar/detail-modal.blade.php:157-205`](file:///c:/laragon/www/bookqu/resources/views/owner/partials/calendar/detail-modal.blade.php#L157-L205)
- **Deskripsi**: Seluruh kolom input form walk-in di kalender tidak memiliki `value="{{ old(...) }}"`. Validasi error menutup modal dan menghapus seluruh ketikan nama, telepon, dan catatan yang diinput owner.
- **Rekomendasi Perbaikan**: Pasang helper `old(...)` dan inisialisasi `walkinMode: {{ $errors->any() ? 'true' : 'false' }}`.

#### BUG-T5-12: Ketiadaan Modul Ulasan (Review) pada Portal Payment Group
- **Lokasi Berkas**: [`resources/views/customer/manage/payment-show.blade.php`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-show.blade.php)
- **Deskripsi**: Relasi review di-eager load di controller, namun di tampilan view sama sekali tidak ada formulir atau tombol untuk memberikan rating ulasan pada sesi yang telah selesai.

---

### 3. Cacat Prioritas P2 & P3 (Sedang & Rendah)

- **BUG-T5-13 [P2]**: Label tombol modal konfirmasi pembatalan booking berbunyi "Ya, Hapus" alih-alih "Ya, Batalkan Booking" ([confirm-modal.blade.php#L18](file:///c:/laragon/www/bookqu/resources/views/components/owner/confirm-modal.blade.php#L18)).
- **BUG-T5-14 [P2]**: Ketiadaan pencatatan `BookingLog` saat status diubah menjadi paid / completed di `UpdateBookingStatus.php`.
- **BUG-T5-15 [P2]**: Ketiadaan audit log dan email konfirmasi saat walk-in booking dibuat di `CreateWalkInBooking.php`.
- **BUG-T5-16 [P2]**: Inkonsistensi status multi-slot saat owner mengubah status salah satu slot tanpa menyinkronkan slot pasangannya.
- **BUG-T5-17 [P2]**: Hilangnya pilihan tanggal & slot customer reschedule saat validasi server gagal (`old()` tidak di-bind).
- **BUG-T5-18 [P2]**: Hero banner payment group tetap menampilkan badge hijau "Reservasi Dikonfirmasi" pada sesi yang sudah selesai/batal.
- **BUG-T5-19 [P2]**: Hilangnya flash message sukses/error pada kalender saat walk-in booking disimpan.
- **BUG-T5-20 [P2]**: Rating bintang form review direset ke 0 saat validasi server gagal karena melebihi batas karakter.
- **BUG-T5-21 [P2]**: Ketiadaan pesan error inline di dalam form ulasan `review-section.blade.php`.
- **BUG-T5-22 [P2]**: Teks statis "Pembayaran Berhasil & Terverifikasi" tetap terpampang pada booking pending dan batal di `ticket-card.blade.php`.
- **BUG-T5-23 [P3]**: Peringatan batas waktu reschedule/batal muncul membingungkan pada sesi yang sudah berstatus selesai atau batal.
- **BUG-T5-24 [P3]**: Ketiadaan kartu informasi refund dan timeline aktivitas pada `payment-show.blade.php`.
- **BUG-T5-25 [P3]**: Pesan error pembatalan tersembunyi di belakang backdrop gelap modal di `show.blade.php`.
- **BUG-T5-26 [P3]**: Filter tab "Today" pada owner bookings mengabaikan kombinasi filter status lainnya.
- **BUG-T5-27 [P3]**: Redirect pasca pembatalan melempar pelanggan keluar dari portal payment group ke legacy single view.
- **BUG-T5-28 [P3]**: Silent error pada fetch slot waktu reschedule customer saat terjadi HTTP 4xx.
- **BUG-T5-29 [P3]**: Form reschedule owner menggunakan full-page reload alih-alih AJAX, menutup modal saat terjadi error.
- **BUG-T5-30 [P3]**: Tombol cetak tiket individual tidak menyembunyikan header portal pada mode cetak.
