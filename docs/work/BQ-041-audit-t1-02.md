---
id: BQ-041
title: [BUG-T1-02] Jebakan Redirect Loop pada Tombol "Masuk ke Dashboard Bisnis"
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: public-site
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-1-PUBLIC-AUTH-ONBOARDING.md at a68ffe8
---

# BQ-041: [BUG-T1-02] Jebakan Redirect Loop pada Tombol "Masuk ke Dashboard Bisnis"

Migrated verbatim from the 2026-10 audit (TAHAP-1-PUBLIC-AUTH-ONBOARDING.md, stage 1: Guest Public Pages, Landing Page, Legal, Auth & Owner Onboarding). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: [`resources/views/welcome.blade.php:57`](file:///c:/laragon/www/bookqu/resources/views/welcome.blade.php#L57)
- **Deskripsi & Dampak**:
  Ketika pengguna yang login belum memverifikasi email mengklik tombol *"Masuk ke Dashboard"*, tautan mengarahkan ke `route('owner.dashboard')`. Middleware `verified` mencegat dan me-redirect paksa pengguna ke `/verify-email`. Jika pengguna mengklik logo di halaman verifikasi email, mereka kembali ke beranda dan siklus berulang tanpa kejelasan.
- **Akar Masalah**:
  Kondisi `@auth` pada header landing page mengabaikan status verifikasi:
  ```blade
  @auth
      <a href="{{ route('owner.dashboard') }}" class="craft-btn ...">Dashboard</a>
  @endauth
  ```
- **Rekomendasi Perbaikan**:
  Arahkan secara kondisional:
  ```blade
  @auth
      @if(auth()->user()->hasVerifiedEmail())
          <a href="{{ route('owner.dashboard') }}" class="craft-btn ...">Buka Dashboard</a>
      @else
          <a href="{{ route('verification.notice') }}" class="craft-btn ...">Verifikasi Email Anda</a>
      @endif
  @endauth
  ```

## Evidence

_Fill in when done._
