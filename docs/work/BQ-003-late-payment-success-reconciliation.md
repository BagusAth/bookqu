---
id: BQ-003
title: Reconcile late payment success and verify expiry with every provider
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: payment
requirements: [BR-PAY-001, FR-PAYMENT-008, FR-PAYMENT-010, FR-PAYMENT-011]
invariants: [INV-PAY-02, INV-PAY-03, INV-PAY-06]
launch-blocker: true
needs-lead-approval: true
depends-on: [BQ-028, BQ-067]
---

# BQ-003: Reconcile late payment success and verify expiry with every provider

## Problem

When a payment is already `gagal` (expired by the scheduler or evicted as stale pending) and the provider later reports success, `ProcessSingaPayWebhook` only logs it (`app/Actions/Payment/ProcessSingaPayWebhook.php:201`): money is captured, the booking stays cancelled, no `Refund` is created, nobody is alerted. `bookings:expire-payments` verifies the status with the provider only for Midtrans (`app/Console/Commands/ExpireBookingPayments.php:65`), and SingaPay `checkTransactionStatus` reads the local DB only (`app/Infrastructure/Payments/SingaPay/SingaPayPaymentGateway.php:272`).

> Policy decided in ADR-0004 (decisions 5, 7, 8): record a refund owed (status `pending`, manual return) and alert the owner (notification + mail) plus a `critical` log; the booking stays cancelled. Reviving bookings is deferred.

## Plan

1. Decide the reconciliation policy in an ADR (BQ-028): refund record + owner/admin alert, or revive the booking if the slot is still free.
2. Implement a SingaPay server-side status query (if the API offers one) and use it before expiring.
3. Handle late success in both webhook actions idempotently.
4. Tests: a late success after expiry creates exactly one refund and one alert.

## Evidence

_Fill in when done._
