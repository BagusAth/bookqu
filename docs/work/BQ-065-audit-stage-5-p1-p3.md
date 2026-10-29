---
id: BQ-065
title: Audit stage 5 P1-P3 defects (Customer Self-Service (Tanpa Akun) & Owner Booking Operations)
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: customer-access
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md at a68ffe8
---

# BQ-065: Audit stage 5 — P1, P2 and P3 defects

Migrated verbatim from TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md (Customer Self-Service (Tanpa Akun) & Owner Booking Operations). Status in the current code is unverified (BQ-039). P1 defects that touch a core flow are launch-relevant; promote any of them to its own item when work starts.

> BUG-T5-08 overlaps BQ-012.

## P1 checklist

- [ ] BUG-T5-07: Slot Waktu Jam Lampau (Past Time) Tetap Muncul pada Modal Reschedule Owner
- [ ] BUG-T5-08: Customer Reschedule Mengabaikan Tanggal Blokir / Libur Merchant
- [ ] BUG-T5-09: Tombol Unduh Invoice Mengarah ke HTTP 404 pada Booking Pending / Cancelled
- [ ] BUG-T5-10: Status Invoice Tercetak "LUNAS / SUKSES" untuk Reservasi yang Sudah Batal
- [ ] BUG-T5-11: Data Form Walk-In di Kalender Terhapus Bersih Saat Validasi Gagal (Data Loss)
- [ ] BUG-T5-12: Ketiadaan Modul Ulasan (Review) pada Portal Payment Group

## P2 / P3 checklist

- [ ] **BUG-T5-13 [P2]**: Label tombol modal konfirmasi pembatalan booking berbunyi "Ya, Hapus" alih-alih "Ya, Batalkan Booking" ([confirm-modal.blade.php#L18](file:///c:/laragon/www/bookqu/resources/views/components/owner/confirm-modal.blade.php#L18)).
- [ ] **BUG-T5-14 [P2]**: Ketiadaan pencatatan `BookingLog` saat status diubah menjadi paid / completed di `UpdateBookingStatus.php`.
- [ ] **BUG-T5-15 [P2]**: Ketiadaan audit log dan email konfirmasi saat walk-in booking dibuat di `CreateWalkInBooking.php`.
- [ ] **BUG-T5-16 [P2]**: Inkonsistensi status multi-slot saat owner mengubah status salah satu slot tanpa menyinkronkan slot pasangannya.
- [ ] **BUG-T5-17 [P2]**: Hilangnya pilihan tanggal & slot customer reschedule saat validasi server gagal (`old()` tidak di-bind).
- [ ] **BUG-T5-18 [P2]**: Hero banner payment group tetap menampilkan badge hijau "Reservasi Dikonfirmasi" pada sesi yang sudah selesai/batal.
- [ ] **BUG-T5-19 [P2]**: Hilangnya flash message sukses/error pada kalender saat walk-in booking disimpan.
- [ ] **BUG-T5-20 [P2]**: Rating bintang form review direset ke 0 saat validasi server gagal karena melebihi batas karakter.
- [ ] **BUG-T5-21 [P2]**: Ketiadaan pesan error inline di dalam form ulasan `review-section.blade.php`.
- [ ] **BUG-T5-22 [P2]**: Teks statis "Pembayaran Berhasil & Terverifikasi" tetap terpampang pada booking pending dan batal di `ticket-card.blade.php`.
- [ ] **BUG-T5-23 [P3]**: Peringatan batas waktu reschedule/batal muncul membingungkan pada sesi yang sudah berstatus selesai atau batal.
- [ ] **BUG-T5-24 [P3]**: Ketiadaan kartu informasi refund dan timeline aktivitas pada `payment-show.blade.php`.
- [ ] **BUG-T5-25 [P3]**: Pesan error pembatalan tersembunyi di belakang backdrop gelap modal di `show.blade.php`.
- [ ] **BUG-T5-26 [P3]**: Filter tab "Today" pada owner bookings mengabaikan kombinasi filter status lainnya.
- [ ] **BUG-T5-27 [P3]**: Redirect pasca pembatalan melempar pelanggan keluar dari portal payment group ke legacy single view.
- [ ] **BUG-T5-28 [P3]**: Silent error pada fetch slot waktu reschedule customer saat terjadi HTTP 4xx.
- [ ] **BUG-T5-29 [P3]**: Form reschedule owner menggunakan full-page reload alih-alih AJAX, menutup modal saat terjadi error.
- [ ] **BUG-T5-30 [P3]**: Tombol cetak tiket individual tidak menyembunyikan header portal pada mode cetak.

## P1 details

### BUG-T5-07: Slot Waktu Jam Lampau (Past Time) Tetap Muncul pada Modal Reschedule Owner

- **Lokasi Berkas**: 
  - [`app/Actions/Schedule/GetAvailableSchedules.php:230-260`](file:///c:/laragon/www/bookqu/app/Actions/Schedule/GetAvailableSchedules.php#L230-L260)
  - [`app/Actions/Booking/RescheduleBooking.php:107-110`](file:///c:/laragon/www/bookqu/app/Actions/Booking/RescheduleBooking.php#L107-L110)
- **Deskripsi**: Method `getSlotsForReschedule` tidak memfilter jam yang sudah lewat untuk hari ini. Owner dapat mengklik tombol "Pilih" pada slot jam 08:00 di siang hari, namun saat disubmit server menolak dengan exception `SLOT_NOT_FOUND`.
- **Rekomendasi Perbaikan**: Filter jam lampau jika tanggal yang dipilih sama dengan hari ini WIB.

### BUG-T5-08: Customer Reschedule Mengabaikan Tanggal Blokir / Libur Merchant

- **Lokasi Berkas**: 
  - [`app/Http/Controllers/Customer/BookingManageController.php:303-326`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Customer/BookingManageController.php#L303-L326)
  - [`app/Actions/Booking/RescheduleBooking.php:86-120`](file:///c:/laragon/www/bookqu/app/Actions/Booking/RescheduleBooking.php#L86-L120)
- **Deskripsi**: Alur reschedule mandiri pelanggan tidak memeriksa tabel `owner_blocked_dates`, memungkinkan pelanggan memindahkan jadwal reservasi ke tanggal libur operasional toko merchant.
- **Rekomendasi Perbaikan**: Validasi tanggal terpilih terhadap `owner_blocked_dates`.

### BUG-T5-09: Tombol Unduh Invoice Mengarah ke HTTP 404 pada Booking Pending / Cancelled

- **Lokasi Berkas**: [`resources/views/customer/partials/manage/ticket-card.blade.php:146-155`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/manage/ticket-card.blade.php#L146-L155)
- **Deskripsi**: Tombol invoice muncul pada seluruh status booking. Pada status pending atau cancelled, mengkliknya langsung membuka halaman 404 Not Found karena controller memvalidasi `status === 'sukses'`.
- **Rekomendasi Perbaikan**: Sembunyikan tombol invoice jika status bukan `paid` atau `completed`.

### BUG-T5-10: Status Invoice Tercetak "LUNAS / SUKSES" untuk Reservasi yang Sudah Batal

- **Lokasi Berkas**: [`resources/views/customer/manage/payment-invoice.blade.php:58-60`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-invoice.blade.php#L58-L60)
- **Deskripsi**: Dokumen faktur payment group meng-hardcode badge "LUNAS / SUKSES". Transaksi yang seluruh sesinya telah dibatalkan atau direfund tetap tercetak sebagai dokumen berstatus lunas.
- **Rekomendasi Perbaikan**: Buat badge status dokumen dinamis mengevaluasi status agregat seluruh booking.

### BUG-T5-11: Data Form Walk-In di Kalender Terhapus Bersih Saat Validasi Gagal (Data Loss)

- **Lokasi Berkas**: [`resources/views/owner/partials/calendar/detail-modal.blade.php:157-205`](file:///c:/laragon/www/bookqu/resources/views/owner/partials/calendar/detail-modal.blade.php#L157-L205)
- **Deskripsi**: Seluruh kolom input form walk-in di kalender tidak memiliki `value="{{ old(...) }}"`. Validasi error menutup modal dan menghapus seluruh ketikan nama, telepon, dan catatan yang diinput owner.
- **Rekomendasi Perbaikan**: Pasang helper `old(...)` dan inisialisasi `walkinMode: {{ $errors->any() ? 'true' : 'false' }}`.

### BUG-T5-12: Ketiadaan Modul Ulasan (Review) pada Portal Payment Group

- **Lokasi Berkas**: [`resources/views/customer/manage/payment-show.blade.php`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-show.blade.php)
- **Deskripsi**: Relasi review di-eager load di controller, namun di tampilan view sama sekali tidak ada formulir atau tombol untuk memberikan rating ulasan pada sesi yang telah selesai.

## Evidence

_Fill in when done._
