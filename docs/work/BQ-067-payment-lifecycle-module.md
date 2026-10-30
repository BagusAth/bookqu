---
id: BQ-067
title: Implement the Payment lifecycle module (ADR-0004)
type: refactor
status: approved
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

_Fill in when done._
