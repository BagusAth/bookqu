---
id: BQ-056
title: [BUG-T5-05] Bahaya Fatal Crash HTTP 500 (`Attempt to read property on null`) pada Tabel Owner
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

# BQ-056: [BUG-T5-05] Bahaya Fatal Crash HTTP 500 (`Attempt to read property on null`) pada Tabel Owner

Migrated verbatim from the 2026-10 audit (TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md, stage 5: Customer Self-Service (Tanpa Akun) & Owner Booking Operations). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: 
  - [`resources/views/owner/partials/bookings/desktop-table.blade.php:31,78,99`](file:///c:/laragon/www/bookqu/resources/views/owner/partials/bookings/desktop-table.blade.php#L31)
  - [`resources/views/owner/partials/bookings/mobile-cards.blade.php:15,81`](file:///c:/laragon/www/bookqu/resources/views/owner/partials/bookings/mobile-cards.blade.php#L15)
- **Deskripsi & Dampak**:
  Properti `$booking->layanan->namalayanan` dan `$booking->layanan->harga` diakses tanpa nullsafe operator `?->`. Pada PHP 8+, jika relasi layanan null (misal layanan terhapus), pemanggilan langsung memicu fatal error HTTP 500 dan melumpuhkan dashboard booking owner.
- **Rekomendasi Perbaikan**: Gunakan nullsafe operator: `$booking->layanan?->namalayanan` dan `$booking->layanan?->harga ?? 0`.

## Evidence

_Fill in when done._
