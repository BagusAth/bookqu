---
id: BQ-048
title: [BUG-T3-02] JavaScript TDZ ReferenceError Mematikan Seluruh Interaksi Halaman Pembayaran
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
source: docs/audit-bug/TAHAP-3-CUSTOMER-BOOKING-JOURNEY.md at a68ffe8
---

# BQ-048: [BUG-T3-02] JavaScript TDZ ReferenceError Mematikan Seluruh Interaksi Halaman Pembayaran

Migrated verbatim from the 2026-10 audit (TAHAP-3-CUSTOMER-BOOKING-JOURNEY.md, stage 3: Customer Booking Journey: Storefront & 6-Langkah Reservasi hingga Checkout). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: [`resources/views/customer/partials/payment/scripts.blade.php:275-330`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/scripts.blade.php#L275-L330)
- **Deskripsi & Dampak**:
  Pada `DOMContentLoaded`, fungsi `updateCountdown()` dipanggil pada baris 318 sebelum deklarasi `const timer = setInterval(...)` pada baris 319. Di dalam `updateCountdown()`, baris 279 memeriksa `if (timer) clearInterval(timer)`. Jika sisa detik <= 0 saat halaman dimuat, peramban melempar:
  `Uncaught ReferenceError: Cannot access 'timer' before initialization`.
  **Eksekusi script mati seketika**. Listener tombol "Bayar Sekarang" (baris 324) dan tombol "Periksa Status" (baris 328) tidak pernah terpasang, membuat halaman membeku total.
- **Rekomendasi Perbaikan**:
  Pindahkan deklarasi variabel ke awal fungsi:
  ```javascript
  let timer = null;
  let remainingSeconds = {{ max(0, $payment->expired_at ? (int) now()->diffInSeconds($payment->expired_at, false) : 900) }};
  ```

## Evidence

_Fill in when done._
