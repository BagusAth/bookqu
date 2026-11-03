---
id: BQ-057
title: [BUG-T5-06] Ketiadaan Total Komponen Walk-in Modal pada Halaman Bookings Management
type: bug-fix
status: rejected
priority: P0
phase: 4
domain: booking
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-039, BQ-081]
source: docs/audit-bug/TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md at a68ffe8
---

# BQ-057: [BUG-T5-06] Ketiadaan Total Komponen Walk-in Modal pada Halaman Bookings Management

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-135](../BQ-135-booking-page-and-panel.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-135.


Migrated verbatim from the 2026-10 audit (TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md, stage 5: Customer Self-Service (Tanpa Akun) & Owner Booking Operations). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: [`resources/views/owner/bookings.blade.php:15-20`](file:///c:/laragon/www/bookqu/resources/views/owner/bookings.blade.php#L15-L20)
- **Deskripsi & Dampak**:
  Meskipun action `CreateWalkInBooking` dan route backend telah tersedia, di halaman `/owner/bookings` sama sekali tidak ada tombol "+ Walk-in Booking" dan tidak ada komponen modal walk-in. Owner yang sedang melihat daftar booking tidak bisa mendaftarkan tamu on-site secara langsung tanpa berpindah ke kalender.
- **Rekomendasi Perbaikan**:
  Buat komponen mandiri `resources/views/components/owner/modal-walkin.blade.php` dengan AJAX slot picker dan pasang tombol trigger di header `owner/bookings.blade.php`.

## Evidence

Skipped Sesi 4: uji POST walk-in tanpa email gagal NOT NULL bookings.email meskipun validator nullable. Bug dicatat BQ-081; Action Booking/skema dilarang disentuh. UI baru dan uji eksplorasinya ditarik bersama scope yang belum dapat dikirim, bukan diubah agar tampak lulus.

Release fixes 2026-11-01: the backend blocker (BQ-081) is resolved, so walk-in without e-mail now works. What remains is the missing walk-in modal on the bookings page; walk-in already works from the owner calendar, so this is a UI convenience.
