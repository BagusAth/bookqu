---
id: BQ-067
title: Implement the Payment lifecycle module (ADR-0004)
type: refactor
status: done
priority: P0
phase: 4
domain: payment
requirements: [FR-PAYMENT-003, FR-PAYMENT-005, FR-PAYMENT-007, FR-PAYMENT-008, FR-PAYMENT-010, FR-PAYMENT-011, BR-PAY-001]
invariants: [INV-PAY-01, INV-PAY-02, INV-PAY-03, INV-PAY-04, INV-PAY-06, INV-BOOK-06]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-023]
---

# BQ-067: Implement the Payment lifecycle module

Design approved in ADR-0004 (grilling session 2026-10-30). First item of the Phase 4 payment-correctness stream; BQ-002, BQ-003, BQ-013, BQ-014, BQ-015 and BQ-068 build on it.

## Problem

A terminal payment status is written at 12 sites in 10 modules with different guards (ADR-0004 → Context). Only `SynchronizePaymentStatus` locks the row and checks terminal state, group and tenant; `PaymentState` transitions are never used in production.

## Plan

1. Characterization: run and read the existing payment tests; add missing ones for each of the 12 sites' current behaviour.
2. Write the new test suite at the module's interface first (transitions, terminal guard, idempotency, typed result, late success → one refund + alert, owner settlement of a whole group, payer cancel, expiry, initiation failure, free and walk-in payments).
3. Build the module in `app/Actions/Payment/` with the booking-group and subscription adapters behind an internal seam (ADR-0004 decisions 2–4). Locking follows ADR-0005 decision 4 from the start: resolve the payment's tenant, lock the tenant row first, then lock and reload the payment; join the caller's outer transaction when one is open (ADR-0005 decision 3).
4. Move the 12 sites one by one to request transitions; run the full suite after each move.
5. Delete `ExpirePayment` and `App\Services\MidtransPaymentService` once unused; delete the old duplicated tests once the new suite covers them (replace, don't layer).
6. Add the architecture-test rule "only the Payment lifecycle module writes `payments.status`" (BQ-025).
7. Update `docs/domains/payment.md` (operations, invariants, known gaps) and `docs/glossary.md` if names change.

## Invariants at risk

INV-PAY-01…06 and INV-BOOK-06 (multi-slot group consistency). Every move keeps the full suite green; row locks proven on MySQL (BQ-023).

## Out of scope

Platform balance crediting (BQ-015), provider gateway cancellation implementations (provider-outcome work), payment deadline unification (review candidate #6).

## Evidence

Built on branch `bq-067-payment-lifecycle` (2026-10-30).

- **Module:** `App\Actions\Payment\PaymentLifecycle`:
  - `transition()` returns a `PaymentTransitionResult` with a `PaymentTransitionOutcome`;
  - `recordSettled()`, `refundCancelledBooking()`;
  - `App\Domain\Payment\PaymentTransition` lists the six reasons.
- **Internal seam:** `Lifecycle\PaymentConsequences` with `BookingGroupConsequences` and `SubscriptionConsequences`.
- **Locking:**
  - tenant row first, then payment (ADR-0005 decision 4);
  - joins an open outer transaction;
  - restores the caller's tenant context.
- **All 12 sites moved:**
  - provider success/failure (`SynchronizePaymentStatus`, now a mapper);
  - free (`CreateBookingPayment`) and walk-in (`CreateWalkInBooking`) via `recordSettled`;
  - owner settlement (`UpdateBookingStatus`, whole group, `cash`);
  - booking initiation failure (`CreateBooking`);
  - subscription initiation failure (`CreateSubscriptionPayment`; the Midtrans gateway no longer writes status);
  - payer cancel and expiry (`Customer/BookingController`, `Owner/OwnerCheckoutController`, `EvictStalePendingBookings`, `MidtransPaymentService::expirePayment`).
  - Refunds: `CancelBooking` asks the module.
  - The SingaPay webhook no longer swallows a success for a `gagal` payment, so late success reaches the module.
- **Decisions taken while building** (lead delegation 2026-10-30):
  - A provider success for a still-pending group in which a booking was already cancelled is treated like a late success: the group is withdrawn (payment `gagal`, pending siblings cancelled), one refund is owed and the owner is alerted. Before, the success was ignored and the payment stayed `pending`, so the received money was not tracked. `PaymentGroupManagementTest::test_pending_payment_with_cancelled_booking_does_not_resurrect_on_settlement` keeps its intent (nothing resurrected, no booking paid); its last assertion now checks "not paid" plus one refund instead of "still pending".
  - Owner settlement does not send the "new booking" notification or the invoice (as before) and does not record a usage log (as before).
  - Owner marking paid/completed on a `gagal` payment is refused; before, it overwrote `gagal` with `sukses`.
- **Schema:** `refunds.booking_id` is nullable (expand-only migration), so a late subscription success can owe a refund.
- **Deleted:** `ExpirePayment`. `MidtransPaymentService` stays as a thin facade until BQ-032 moves its callers (the customer status poll and callback); recorded in `docs/domains/payment.md`.
- **Tests:**
  - `tests/Feature/Payment/PaymentLifecycleTest.php` (20 tests at the module interface);
  - `tests/Feature/Actions/Payment/PaymentLifecycleLockingTest.php` (MySQL group: waits on the tenant lock and on the payment lock; mutation-checked by removing the tenant lock);
  - the `ExpirePayment` tests in `PaymentActionTest` and `RF09StabilityAndHardeningTest` now call the module (the duplicate in `PaymentActionTest` was removed).
- **Architecture test:** the `payment-status-writers` rule exempts only the lifecycle module. Allowlist shrank from 39 to 19, and what remains are status values in JSON responses (BQ-032). `scope-bypass-reason` lost 6; `provider-sdk` lost 2 unused SDK imports.
- **Larastan:** no new errors; the baseline lost 66 lines of entries for deleted code.
- **Local runs:** SQLite suite 445 tests pass; lifecycle lock tests pass on MySQL 8.4.
- **GitHub Actions** run https://github.com/BagusAth/bookqu/actions/runs/41086120502 (PR #4): Quality checks, SQLite, MySQL and MariaDB all passed.
