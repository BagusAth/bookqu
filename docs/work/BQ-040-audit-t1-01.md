---
id: BQ-040
title: [BUG-T1-01] Tombol "Lupa Password?" pada Login Mengarah ke `#` (Dead Link)
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
source: docs/audit-bug/TAHAP-1-PUBLIC-AUTH-ONBOARDING.md at a68ffe8
---

# BQ-040: [BUG-T1-01] Tombol "Lupa Password?" pada Login Mengarah ke `#` (Dead Link)

Migrated verbatim from the 2026-10 audit (TAHAP-1-PUBLIC-AUTH-ONBOARDING.md, stage 1: Guest Public Pages, Landing Page, Legal, Auth & Owner Onboarding). Status in the current code is unverified — check it first (BQ-039).

> Spot check 2026-10-30: the login page now links to `route('password.request')` — looks fixed; confirm and close.

## Audit finding

- **Lokasi Berkas**: [`resources/views/auth/login.blade.php:73`](file:///c:/laragon/www/bookqu/resources/views/auth/login.blade.php#L73)
- **Deskripsi & Dampak**:
  Tautan berbunyi *"Lupa kata sandi?"* menggunakan atribut statis `<a href="#" class="...">Lupa password?</a>`. Tidak ada route Laravel (`password.request`), controller, maupun form modal yang menanganinya. Pengguna yang lupa kata sandi terkunci permanen di luar akun mereka tanpa ada alur pemulihan kata sandi mandiri.
- **Akar Masalah**:
  ```blade
  <div class="text-right">
      <a href="#" class="text-xs font-semibold text-bq-primary hover:underline">
          Lupa password?
      </a>
  </div>
  ```
- **Rekomendasi Perbaikan**:
  Sediakan route pemulihan sandi (atau sembunyikan sementara jika fitur belum rilis sesuai ADR-0002) atau arahkan ke bantuan dukungan admin:
  ```blade
  <a href="{{ route('password.request') }}" class="text-xs font-semibold text-bq-primary hover:underline">
      Lupa password?
  </a>
  ```

## Evidence

_Fill in when done._
