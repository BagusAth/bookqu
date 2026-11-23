---
id: BQ-002
title: Require authorization to cancel a pending payment and cancel it at the gateway
type: bug-fix
status: done
priority: P0
phase: 4
domain: payment
requirements: [FR-MANAGE-008, FR-MANAGE-009, FR-MANAGE-010, FR-PAYMENT-010, NFR-SEC-008]
invariants: [INV-TOK-01, INV-PAY-03]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-067, BQ-001, BQ-032]
---

# BQ-002: Require authorization to cancel a pending payment and cancel it at the gateway

Plan approved via ADR-0004/ADR-0006 and [ADR-0007](../../adr/ADR-0007-provider-outcome-intake.md) (gateway cancel best-effort after commit), 2026-10-30.

## Problem

`POST …/booking/payment/{order_id}/cancel` (`BookingController::cancelPayment`, line 803) cancels any pending payment of the tenant and its bookings with only the `order_id`. It also never cancels the transaction at SingaPay/Midtrans, so the customer can still pay a cancelled order (audit BUG-T4-01, BQ-049).

> ADR-0004: payer cancel is a Payment lifecycle transition; gateway cancellation is requested after commit (decision 9).

> ADR-0006 defines payer authorization; BQ-001 implements it. ADR-0007 defines best-effort provider cancellation after commit, implemented by BQ-032. This item verifies their complete payer-cancel integration.

## Plan

1. Require BQ-001/ADR-0006's exact pending-payment cancellation capability and matching family/tenant; revalidate it inside the tenant-first transaction. Retain CSRF and delegate the transition to ADR-0004's Payment lifecycle. Booking-action grants and public identifiers do not authorize this endpoint.
2. Add a gateway cancellation method to `PaymentGatewayInterface` (or expire the payment link) and call it after commit.
3. Tests for identifier-only/wrong-profile/foreign-family/revoked access, terminal/expired payment refusal, authorized whole-group cancellation, and gateway cancellation being requested after commit. Return behavior follows the stored lifecycle outcome; do not trust the browser's result.

## Evidence

Selesai 2026-10-31, sesudah BQ-001 dan BQ-032.

- Semua request memerlukan grant Checkout atau Management untuk tenant/payment yang tepat. Booking-selection session, token legacy, grant expired/revoked dan grant standalone booking tidak mengizinkan pembatalan. Matriks lintas payment/tenant juga tetap dibuktikan CustomerAccessTest dari BQ-001.
- CancelCheckoutPayment memeriksa ulang grant setelah kunci tenant, me-refresh payment, menolak status terminal serta deadline lewat/hilang, lalu memakai PaymentLifecycle::transition(CancelledByPayer). Penolakan lifecycle tidak lagi menghasilkan respons sukses.
- Tiga regresi dibuktikan gagal sebelum perbaikan: tepat pada deadline, deadline hilang, dan lifecycle rejection. PaymentRules kini menetapkan expired saat now >= expired_at; model Payment mendelegasikan expiry/payability ke aturan tersebut. Settlement margin/occupancy tidak berubah.
- Pembatalan mengubah satu payment menjadi gagal dan seluruh pending booking grup menjadi cancelled, tanpa refund awal. Request ulang ditolak tanpa mengulang cancel gateway. Provider tetap mengikuti payment lama ketika default gateway berubah.
- Kegagalan gateway tidak membalikkan keputusan lokal. SingaPay yang belum mempunyai cancel API tetap no-op sesuai ADR-0007; tidak ada klaim link eksternalnya telah dinonaktifkan. Provider success terlambat menghasilkan satu refund owed dan tidak menghidupkan booking, termasuk callback duplikat.
- CancelCheckoutPaymentTest: 19 kasus HTTP/application mencakup dua profil, terminal/expiry boundaries, replay, stale authorization, CSRF, gateway failure dan late success.
- CancelCheckoutPaymentTransactionTest (MySQL): gateway mengamati payment/booking yang sudah commit dari koneksi kedua pada transaction level 0 dan dapat mengambil kunci tenant. Rollback transaksi terluar mengembalikan data pending dan tidak memanggil gateway. Fixture ditruncate setelah tes.
- Verifikasi akhir: lihat PR branch bq-002-payer-cancellation; SQLite 530 tes / 2.632 assertions setelah sinkronisasi agathan (BQ-076), PHPStan tanpa error (baseline turun dua entri), Pint lulus. MySQL penuh 538 tes / 2.660 assertions lulus.

Batas scope: pembatalan oleh owner atas pending booking dan kemampuan cancel SingaPay tetap dicatat di BQ-049. Keduanya tidak diubah diam-diam oleh jalur payer ini. API provider live tidak dipanggil selama pengujian.
