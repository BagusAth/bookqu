# ADR-0004: Payment lifecycle module

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead)
> **Work item:** BQ-067 (implementation), BQ-028 (Phase 2 package)

## Context

The 2026-10-30 architecture review (`/improve-codebase-architecture`, payment hot spot) found that a terminal payment status is written at **12 sites in 10 modules**: `sukses` in `SynchronizePaymentStatus` (×2), `CreateBookingPayment` + `CreateBooking` (free path), `CreateWalkInBooking`, `UpdateBookingStatus`; `gagal` in `SynchronizePaymentStatus`, `ExpirePayment`, `Customer/BookingController::cancelPayment`, `CreateBooking`, `CreateSubscriptionPayment`, `MidtransPaymentGateway`, `OwnerCheckoutController`. Only `SynchronizePaymentStatus` applies the full rule set (row lock, terminal guard, payment-group and tenant guards); `PaymentState::canTransition` / `isTerminal` have no production caller. Consequences:

- `UpdateBookingStatus` marks a shared payment `sukses` when the owner marks one slot of a multi-slot group; the provider webhook then returns early and the sibling bookings are evicted although the money arrived (BQ-068).
- A provider success for a payment already `gagal` is only logged (BQ-003).
- Every queued payment item — BQ-002, BQ-003, BQ-013, BQ-014, BQ-015 — would have to touch up to 12 sites.
- Results are untyped arrays; one path reports `gagal` while the row stays `pending`.

## Decision

1. **One module owns every payment transition.** All 12 sites — booking and subscription payments, provider outcome, expiry, payer cancel, checkout-initiation failure, owner-recorded settlement, payments created already settled (free, walk-in) — request transitions from the Payment lifecycle module. No other code writes `payments.status`; an architecture test enforces it (BQ-025).
2. **Rules:** transitions are validated by `App\Domain\Payment\PaymentState` (`pending → sukses | gagal`, both terminal), applied under a row lock inside one transaction, and idempotent (a repeated request returns the current state and changes nothing).
3. **Location and seam:** the module lives in `app/Actions/Payment/` (it owns the transaction and lock). Consequences fan out through an internal seam keyed by payment purpose with two adapters: **booking group** (mark bookings paid/cancelled, tokens, booking log, notifications, cache) and **subscription** (activate/extend). `ExpirePayment` and `App\Services\MidtransPaymentService` are deleted once their callers have moved.
4. **Result:** every request returns a typed result that states the payment's actual stored status and what happened: `changed`, `already-final`, `rejected` (with reason), or `refund-owed`.
5. **Late success** (provider reports success for a payment already `gagal`): record a refund owed and alert; the booking stays cancelled. Reviving the booking when all slots are still free is deferred until the Slot bookability module exists.
6. **Owner-recorded settlement** (owner marks a booking paid or completed): applies to the **whole payment group**, records the payment `sukses` with `metode = cash`, and requests gateway cancellation of the open link. Single-slot marking of a group is no longer possible.
7. **Refunds are created only by this module** — at most one per payment — for customer cancellation of a paid booking and for late success. `CancelBooking` requests it. Refunds are recorded with status `pending`; money is returned manually and marked `processed` (admin flow: BQ-038). No provider refund API.
8. **Alerts** for late success: owner notification (database + mail) and a `critical` log entry for the platform until production alerting exists (BQ-021).
9. **Gateway cancellation** is requested after commit when a payer cancels, a payment expires or the owner settles. Provider implementations come with the provider-outcome work (candidate #4 of the review); until then the call is a logged no-op.
10. **Platform balance crediting is out of scope.** The booking-group adapter is the future hook for BQ-015 once fees and settlement are decided.

## Consequences

- One place to implement and test BQ-002, BQ-003, BQ-013, BQ-014 and BQ-015; the payment-group split (BQ-068) is fixed by construction.
- Tests: one suite at the module's interface replaces ~15 tests that assert the same invariants through three entry points (`PaymentActionTest`, `PaymentGroupManagementTest`, `P0SecurityTest`, `ProductionLogicSpecificationTest`, `SingaPayIntegrationTest`); old tests are deleted only after the new suite covers the same behaviour. Row-lock behaviour is proven on MySQL (BQ-023).
- Order: ADR now (Phase 2) → MySQL suite (Phase 3) → BQ-067 is the first item of the Phase 4 payment-correctness stream.
- Booking actions stop writing payment columns; payment modules stop containing ~100 lines of booking-group logic.
