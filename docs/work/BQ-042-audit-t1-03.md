---
id: BQ-042
title: [BUG-T1-03] Ketiadaan Proteksi CSRF & Token Expired Handling pada Tombol Logout di Notice Verifikasi
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

# BQ-042: [BUG-T1-03] Ketiadaan Proteksi CSRF & Token Expired Handling pada Tombol Logout di Notice Verifikasi

Migrated verbatim from the 2026-10 audit (TAHAP-1-PUBLIC-AUTH-ONBOARDING.md, stage 1: Guest Public Pages, Landing Page, Legal, Auth & Owner Onboarding). Status in the current code is unverified — check it first (BQ-039).

> Spot check 2026-10-30: the verify-email logout form has `@csrf` — likely fixed; confirm expired-token handling and close.

## Audit finding

- **Lokasi Berkas**: [`resources/views/auth/verify-email.blade.php:48`](file:///c:/laragon/www/bookqu/resources/views/auth/verify-email.blade.php#L48)
- **Deskripsi & Dampak**:
  Tombol Logout di halaman verifikasi email sering menghasilkan error `419 Page Expired` jika tab ditinggalkan terbuka lebih dari masa kedaluwarsa sesi CSRF. Tidak ada mekanisme regenerasi token otomatis.

## Evidence

_Fill in when done._
