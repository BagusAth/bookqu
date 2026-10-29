---
id: BQ-051
title: [BUG-T4-03] Polling Status Mengalami Runaway Loop Tanpa Exponential Backoff
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: payment
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-4-PAYMENT-GATEWAY-EXPIRY.md at a68ffe8
---

# BQ-051: [BUG-T4-03] Polling Status Mengalami Runaway Loop Tanpa Exponential Backoff

Migrated verbatim from the 2026-10 audit (TAHAP-4-PAYMENT-GATEWAY-EXPIRY.md, stage 4: Payment Gateway, Webhooks, Halaman Pembayaran & Expiry Engine). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: [`resources/views/customer/partials/payment/scripts.blade.php:183-261`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/scripts.blade.php#L183-L261)
- **Deskripsi & Dampak**:
  Fungsi auto-polling status berjalan setiap 2,5 detik tanpa batas percobaan maksimal (*max retries*) dan tanpa penanganan status error HTTP 419/500. Jika koneksi bermasalah atau sesi kedaluwarsa, script terus menembak server ribuan kali tanpa henti (menciptakan DoS lokal ke server web).
- **Rekomendasi Perbaikan**: Batasi polling maksimal 120 iterasi (5 menit) dan hentikan jika terjadi 3 kegagalan HTTP berturut-turut.

## Evidence

_Fill in when done._
