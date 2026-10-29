---
id: BQ-058
title: [BUG-T6-01] Karakter Kutip pada Nama Bisnis Menyebabkan Crash Total JavaScript di Halaman Settings
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: tenancy
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-6-CRM-REVIEWS-BILLING-SETTINGS.md at a68ffe8
---

# BQ-058: [BUG-T6-01] Karakter Kutip pada Nama Bisnis Menyebabkan Crash Total JavaScript di Halaman Settings

Migrated verbatim from the 2026-10 audit (TAHAP-6-CRM-REVIEWS-BILLING-SETTINGS.md, stage 6: CRM Pelanggan, Moderasi Ulasan, Billing Langganan & Pengaturan Akun). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: [`resources/views/owner/settings.blade.php:6-11`](file:///c:/laragon/www/bookqu/resources/views/owner/settings.blade.php#L6-L11)
- **Deskripsi & Dampak (Fatal Crash Halaman Settings)**:
  Inisialisasi `targetBusiness: '{{ strtolower(trim($tenant->namabisnis ?? '')) }}'` menggunakan tanda kutip tunggal Blade mentah. Jika nama bisnis mengandung tanda petik (misal: *Bob's Barber* atau *D'Salon*), evaluasi Blade menghasilkan string terputus `targetBusiness: 'bob's barber'`, yang langsung memicu:
  `Uncaught SyntaxError: Unexpected identifier 's'`.
  Karena error terjadi pada root node `x-data`, **seluruh Alpine.js di halaman Settings lumpuh total**: tombol show/hide password mati, tombol Danger Zone "Delete Account" mati, dan toggle profil tidak merespons sama sekali.
- **Rekomendasi Perbaikan**:
  Ganti dengan directive aman bawaan Laravel Blade:
  ```blade
  targetBusiness: @js(strtolower(trim($tenant->namabisnis ?? ''))),
  targetAccount: @js(strtolower(trim($tenant->user?->email ?? auth()->user()?->email ?? '')))
  ```

## Evidence

_Fill in when done._
