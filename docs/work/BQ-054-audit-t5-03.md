---
id: BQ-054
title: [BUG-T5-03] Mismatch Total 6 Atribut Payload `$bookingData` Merusak Modal Reschedule Owner
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: booking
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md at a68ffe8
---

# BQ-054: [BUG-T5-03] Mismatch Total 6 Atribut Payload `$bookingData` Merusak Modal Reschedule Owner

Migrated verbatim from the 2026-10 audit (TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md, stage 5: Customer Self-Service (Tanpa Akun) & Owner Booking Operations). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

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

## Evidence

_Fill in when done._
