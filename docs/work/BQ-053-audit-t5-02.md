---
id: BQ-053
title: [BUG-T5-02] Fatal Token Scope Mismatch pada Aksi Reschedule & Batal di Portal Payment Group
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

# BQ-053: [BUG-T5-02] Fatal Token Scope Mismatch pada Aksi Reschedule & Batal di Portal Payment Group

Migrated verbatim from the 2026-10 audit (TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md, stage 5: Customer Self-Service (Tanpa Akun) & Owner Booking Operations). Status in the current code is unverified — check it first (BQ-039).

> Related to BQ-001 (manage_token acting as master token).

> Confirmed 2026-10-30 (architecture review): `show.blade.php:106` always includes the cancel modal, which embeds `cancellation_token` (`cancel-modal:87`); `action-panel:52` and `payment-show:295/434` embed tokens too, so any one management token yields every capability. Route-level scope checks (RF09 `test_p1_01_token_scope_separation`) pass regardless. Addressed together with BQ-001 (review candidate #3 "Management link module").

## Audit finding

- **Lokasi Berkas**: 
  - [`resources/views/customer/manage/payment-show.blade.php:295,434`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-show.blade.php#L295)
  - [`app/Http/Controllers/Customer/BookingManageController.php:176-187`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Customer/BookingManageController.php#L176-L187)
- **Deskripsi & Dampak**:
  Pada portal payment group, tautan reschedule dan cancel menggunakan `$token` (`Payment->manage_token`) sebagai fallback. Di `BookingManageController::resolveBooking`, scope `'cancel'` hanya menerima `cancellation_token` dan `'reschedule'` hanya menerima `reschedule_token`. Penggunaan `manage_token` ditolak keras dengan status `HTTP 403 Forbidden: Token tidak valid`. Pelanggan tidak dapat membatalkan atau mengubah jadwal mereka dari portal grup.
- **Rekomendasi Perbaikan**:
  Perbarui `resolveBooking` agar `manage_token` diizinkan untuk scope cancel dan reschedule jika mencakup transaksi tersebut.

## Evidence

_Fill in when done._
