---
id: BQ-003
title: Reconcile late payment success and verify expiry with every provider
type: bug-fix
status: in-progress
priority: P0
phase: 4
domain: payment
requirements: [BR-PAY-001, FR-PAYMENT-008, FR-PAYMENT-010, FR-PAYMENT-011]
invariants: [INV-PAY-02, INV-PAY-03, INV-PAY-06]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-067, BQ-032]
---

# BQ-003: Reconcile late payment success and verify expiry with every provider

Plan approved via ADR-0004 (policy) and [ADR-0007](../adr/ADR-0007-provider-outcome-intake.md) (one intake, provider-first status checks; SingaPay status API to be confirmed in BQ-032), 2026-10-30. Plan step 1 is decided; step 2 is BQ-032/BQ-071.

## Problem

When a payment is already `gagal` (expired by the scheduler or evicted as stale pending) and the provider later reports success, `ProcessSingaPayWebhook` only logs it (`app/Actions/Payment/ProcessSingaPayWebhook.php:201`): money is captured, the booking stays cancelled, no `Refund` is created, nobody is alerted. `bookings:expire-payments` verifies the status with the provider only for Midtrans (`app/Console/Commands/ExpireBookingPayments.php:65`), and SingaPay `checkTransactionStatus` reads the local DB only (`app/Infrastructure/Payments/SingaPay/SingaPayPaymentGateway.php:272`).

> Policy decided in ADR-0004 (decisions 5, 7, 8): record a refund owed (status `pending`, manual return) and alert the owner (notification + mail) plus a `critical` log; the booking stays cancelled. Reviving bookings is deferred.

## Plan

1. ~~Decide the reconciliation policy~~ — decided in ADR-0004 (refund owed + owner alert; no revival).
2. Implement a SingaPay server-side status query (if the API offers one) and use it before expiring.
3. Handle late success in both webhook actions idempotently.
4. Tests: a late success after expiry creates exactly one refund and one alert.

## Evidence

Plan steps 3–4 done by BQ-067 (2026-10-30):
- both webhooks hand a success for a `gagal` payment to the Payment lifecycle, which records exactly one refund owed and one owner alert (`PaymentLifecycleTest::test_late_success_records_one_refund_and_alerts_the_owner`);
- the SingaPay webhook no longer returns early for that case.

Step 2 (provider-first status before expiry, SingaPay status query) remains with BQ-032 and BQ-071.

Step 2 done by BQ-032 (2026-10-30):
- `bookings:expire-payments` and every status check now ask the payment's own provider before expiring (`PaymentOutcomeIntake::check`); SingaPay payments are no longer answered from the local database.
- SingaPay's client has no status endpoint, so SingaPay answers `unknown` and relies on webhooks plus the late-success path. Confirming a SingaPay status API is BQ-074.
