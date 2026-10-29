---
id: BQ-052
title: [BUG-T5-01] Kebocoran Token Rahasia pada Google Calendar & Teks Berbagi WhatsApp
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: customer-access
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md at a68ffe8
---

# BQ-052: [BUG-T5-01] Kebocoran Token Rahasia pada Google Calendar & Teks Berbagi WhatsApp

Migrated verbatim from the 2026-10 audit (TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md, stage 5: Customer Self-Service (Tanpa Akun) & Owner Booking Operations). Status in the current code is unverified — check it first (BQ-039).

> Spot check 2026-10-30: share texts carry a "NO token" comment in `payment-show.blade.php` — likely fixed; confirm and close.

## Audit finding

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

## Evidence

_Fill in when done._
