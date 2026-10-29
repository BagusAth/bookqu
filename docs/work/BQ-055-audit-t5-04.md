---
id: BQ-055
title: [BUG-T5-04] Input Tanggal pada Modal Reschedule Owner Tidak Memiliki Atribut `name="tanggal"`
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

# BQ-055: [BUG-T5-04] Input Tanggal pada Modal Reschedule Owner Tidak Memiliki Atribut `name="tanggal"`

Migrated verbatim from the 2026-10 audit (TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md, stage 5: Customer Self-Service (Tanpa Akun) & Owner Booking Operations). Status in the current code is unverified — check it first (BQ-039).

> Spot check 2026-10-30: no `name="tanggal"` found under `resources/views/owner` — likely still open.

## Audit finding

- **Lokasi Berkas**: [`resources/views/components/owner/modal-reschedule-booking.blade.php:156-163`](file:///c:/laragon/www/bookqu/resources/views/components/owner/modal-reschedule-booking.blade.php#L156-L163)
- **Deskripsi & Dampak**:
  Elemen `<input type="date">` memiliki `x-model="selectedDate"` tetapi **tidak memiliki atribut `name="tanggal"`**. Karena form disubmit secara POST standar, tanggal baru yang dipilih owner tidak pernah dikirimkan ke server. Server menerima `tanggal = null` dan validasi tanggal diabaikan.
- **Rekomendasi Perbaikan**: Tambahkan atribut `name="tanggal"` pada tag input.

## Evidence

_Fill in when done._
