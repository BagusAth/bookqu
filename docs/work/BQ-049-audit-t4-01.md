---
id: BQ-049
title: [BUG-T4-01] Pembatalan Booking Tanpa Membatalkan Transaksi Gateway (Zombie Debits)
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

# BQ-049: [BUG-T4-01] Pembatalan Booking Tanpa Membatalkan Transaksi Gateway (Zombie Debits)

Covered by [ADR-0007](../adr/ADR-0007-provider-outcome-intake.md) decision 6 (best-effort gateway cancel after commit); verify with BQ-032.

Migrated verbatim from the 2026-10 audit (TAHAP-4-PAYMENT-GATEWAY-EXPIRY.md, stage 4: Payment Gateway, Webhooks, Halaman Pembayaran & Expiry Engine). Status in the current code is unverified — check it first (BQ-039).

> Overlaps BQ-002 (gateway cancellation) and BQ-003 (late success).

## Audit finding

- **Lokasi Berkas**: 
  - [`app/Http/Controllers/Customer/BookingController.php:803-838`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Customer/BookingController.php#L803-L838)
  - [`app/Actions/Payment/SynchronizePaymentStatus.php:115-127`](file:///c:/laragon/www/bookqu/app/Actions/Payment/SynchronizePaymentStatus.php#L115-L127)
- **Deskripsi & Dampak**:
  Ketika pelanggan menekan tombol *"Batalkan & Ganti Jadwal"*, sistem mengupdate status pembayaran di database lokal menjadi `'gagal'` dan membatalkan reservasi. Namun sistem **tidak pernah memanggil API cancel** ke Midtrans (`/v2/{order_id}/cancel`) atau menonaktifkan link SingaPay. Jika pelanggan kemudian membayar QRIS/VA yang sudah terlanjur dibuka melalui aplikasi perbankan mereka, webhook BookQu menolaknya sebagai transaksi gagal. **Uang pelanggan terpotong tetapi tidak ada booking yang tercipta dan tidak ada refund otomatis yang dipicu**.
- **Rekomendasi Perbaikan**:
  Panggil gateway cancel sebelum mengubah status lokal menjadi gagal:
  ```php
  $gateway = $this->gatewayManager->forPayment($payment);
  $gateway->cancelTransaction($payment);
  ```

## Evidence

_Fill in when done._
