---
id: BQ-050
title: [BUG-T4-02] Pengiriman Email Sinkronik Memperlambat Webhook dan Menimbulkan Pantulan Invoice Prematur
type: bug-fix
status: done
priority: P0
phase: 4
domain: notifications
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-4-PAYMENT-GATEWAY-EXPIRY.md at a68ffe8
---

# BQ-050: [BUG-T4-02] Pengiriman Email Sinkronik Memperlambat Webhook dan Menimbulkan Pantulan Invoice Prematur

Migrated verbatim from the 2026-10 audit (TAHAP-4-PAYMENT-GATEWAY-EXPIRY.md, stage 4: Payment Gateway, Webhooks, Halaman Pembayaran & Expiry Engine). Status in the current code is unverified — check it first (BQ-039).

> Overlaps BQ-013 (queue notifications).

## Audit finding

- **Lokasi Berkas**: 
  - [`app/Actions/Payment/SynchronizePaymentStatus.php:203-238`](file:///c:/laragon/www/bookqu/app/Actions/Payment/SynchronizePaymentStatus.php#L203-L238)
  - [`app/Mail/BookingGroupInvoiceMail.php:15`](file:///c:/laragon/www/bookqu/app/Mail/BookingGroupInvoiceMail.php#L15)
  - [`app/Notifications/NewBookingOwnerNotification.php:10`](file:///c:/laragon/www/bookqu/app/Notifications/NewBookingOwnerNotification.php#L10)
- **Deskripsi & Dampak**:
  Kedua mailable email tidak mengimplementasikan interface `ShouldQueue`. Di dalam callback transaksi webhook, server melakukan koneksi SMTP sinkron untuk mengirim 2 email yang memakan waktu 2–4 detik. Pelanggan yang selesai membayar di SingaPay hosted checkout langsung di-redirect ke `/invoice` dalam ~500ms. Karena webhook belum selesai, status di DB masih `pending`. Halaman `showInvoice` menolak status pending dan melempar pelanggan kembali ke layar checkout dengan pesan error membingungkan: *"Silakan selesaikan pembayaran terlebih dahulu"*.
- **Rekomendasi Perbaikan**:
  1. Tambahkan `implements ShouldQueue` pada `BookingGroupInvoiceMail` dan `NewBookingOwnerNotification`.
  2. Pada `BookingController@showInvoice`, jika status masih `pending`, lakukan `refresh()` dan toleransi grace period sebelum me-redirect error.

## Evidence

S7 (2026-11-01): removed synchronous customer delivery from the webhook path; owner notification queues after commit. `SingaPayIntegrationTest::test_webhook_paid_status` proves paid state plus queued notification/job and no immediate mail.
The old recommendation to queue `BookingGroupInvoiceMail` directly is superseded by ADR-0006/0013: the ID-only worker mints/sends the credential, so raw links never enter the queue payload. Customer controller/invoice behavior is unchanged.
Verification: full SQLite suite 596 tests / 3,123 assertions; Pint/PHPStan pass, baseline unchanged. Production cron and mail evidence remain BQ-020/021.
