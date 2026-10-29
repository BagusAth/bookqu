---
id: BQ-060
title: [BUG-T6-03] Fatal Error Potensial `Attempt to read property "email" on null` pada Modal Hapus Akun
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: auth
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-6-CRM-REVIEWS-BILLING-SETTINGS.md at a68ffe8
---

# BQ-060: [BUG-T6-03] Fatal Error Potensial `Attempt to read property "email" on null` pada Modal Hapus Akun

Migrated verbatim from the 2026-10 audit (TAHAP-6-CRM-REVIEWS-BILLING-SETTINGS.md, stage 6: CRM Pelanggan, Moderasi Ulasan, Billing Langganan & Pengaturan Akun). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: [`resources/views/owner/settings.blade.php:302`](file:///c:/laragon/www/bookqu/resources/views/owner/settings.blade.php#L302)
- **Deskripsi & Dampak**:
  Pemanggilan `{{ $tenant->user->email }}` tidak menggunakan null-safe operator `?->`. Jika relasi user bernilai null, PHP 8+ langsung melempar fatal exception `Attempt to read property "email" on null` yang menghasilkan crash HTTP 500.
- **Rekomendasi Perbaikan**:
  Ganti menjadi:
  ```blade
  {{ $tenant->user?->email ?? auth()->user()?->email }}
  ```

## Evidence

_Fill in when done._
