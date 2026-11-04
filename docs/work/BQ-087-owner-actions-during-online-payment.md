---
id: BQ-087
title: Lock owner booking actions while an online payment is open, and never ignore a double payment
type: bug-fix
status: in-progress
priority: P1
phase: 4
domain: payment
requirements: [FR-BOOKING-009, FR-BOOKING-014, FR-PAYMENT-010, FR-PAYMENT-011]
invariants: [INV-BOOK-02, INV-BOOK-03, INV-PAY-03, INV-PAY-04, INV-PAY-06]
lane: heavy
approval: tech-lead
launch-blocker: true
depends-on: []
---

# BQ-087: Lock owner booking actions while an online payment is open, and never ignore a double payment

## Problem

While a customer's online payment is still open, the owner can mark the booking paid or cancel it. SingaPay links cannot be cancelled at the gateway (`app/Infrastructure/Payments/SingaPay/SingaPayPaymentGateway.php:351` is a no-op, ADR-0007), so the customer can still pay.

### Case 1: double payment ignored silently

Booking deadline 15 minutes plus a 2-minute settlement margin (`config/payment.php:32-35`).

| Time | Event |
|---|---|
| 10:00 | Customer books; SingaPay link created; booking `pending`, payment `pending` |
| 10:03 | Owner clicks "mark paid" because the customer promised cash. `UpdateBookingStatus` settles the payment as `SettledByOwner`: payment `sukses`, method cash (`app/Actions/Booking/UpdateBookingStatus.php:51-67`) |
| 10:05 | Customer pays the still-open QRIS |
| 10:05 | Webhook: `PaymentLifecycle::transition` sees the payment already `sukses` and returns `AlreadyFinal` (`app/Actions/Payment/PaymentLifecycle.php:73-74`) |

Result: the customer paid twice, the second payment sits in the platform account, no refund is recorded, nobody is alerted. `AlreadyFinal` is correct for a repeated webhook of the *same* online payment (INV-PAY-03), but here the first success came from the owner, not the provider.

### Case 2: owner cancels a pending booking

`CancelBooking` (owner) cancels the bookings but never touches the payment (`app/Actions/Booking/CancelBooking.php:76-116`). The link stays open; a later payment becomes a refund owed (handled since BQ-003, but nobody processes refunds yet, see D-001 in `docs/product-decisions.md`). Previously tracked as the BQ-049 follow-up.

### Case 3: owner cancels a paid booking

Only a customer cancellation records a refund (`CancelBooking.php:114-115`). An owner cancelling a `paid` booking leaves the money with the platform and the owner's balance still credited. The customer gets nothing. The refund rule for this case is Product Lead decision D-001 point 8.

## Decision (Tech Lead, 2026-11-01)

1. While a payment is still awaiting the provider (pending and within deadline + settlement margin, the same hold window `BookingRules` uses for occupancy, INV-BOOK-03), the owner cannot mark the booking paid or completed and cannot cancel it. Enforced server-side; the owner sees "Menunggu pembayaran online, berakhir HH:MM WIB".
2. Owner settlement ("mark paid") is removed for bookings whose payment was created with a provider link. After the window, expiry cancels the booking; a customer who then pays on-site is recorded as a walk-in. The Product Lead is informed because an owner-visible action disappears.
3. Safety net: a provider success for a payment already settled by the owner records one refund owed (reason `double-payment`), logs critical and notifies the owner, exactly like a late success. It stays even with (1), because a webhook can arrive after the window.
4. After the window, an owner cancelling a still-`pending` booking first expires its payment through the lifecycle, then cancels the group, so no open payment is left behind.
5. Case 3 follows D-001 point 8 once the Product Lead answers. Until then it is listed in the domain's known gaps.

## Plan

1. A single rule "payment is awaiting the provider" built on the existing hold window (`App\Domain\Booking\BookingRules` / `PaymentRules`), not a second copy of the deadline arithmetic.
2. `UpdateBookingStatus`: refuse paid/completed/cancelled for a booking whose payment is awaiting the provider; refuse owner settlement for provider-linked payments. Clear Indonesian errors.
3. `CancelBooking` (owner): same guard; after the window, run `PaymentTransition::Expired` for an open payment inside the same transaction, then cancel the group.
4. `PaymentLifecycle::transition`: when `ProviderSucceeded` meets a `sukses` payment whose success was owner-settled, go to the refund-owed path instead of `AlreadyFinal`. Repeated webhooks still yield one refund (INV-PAY-06).
5. Owner booking list and detail: show the waiting state and countdown, hide the actions (presentation only; the server guard is the authority).
6. Close BQ-049 together with this item.

## Invariants at risk

- INV-PAY-03: a repeated webhook for an online success stays `AlreadyFinal`; only an owner-settled payment takes the refund path.
- INV-PAY-06: at most one refund per payment, also for repeated double-payment webhooks.
- INV-BOOK-02/03: cancellation after the window keeps using `BookingState` and the single hold rule.
- Walk-in and free bookings (settled at creation) are unaffected.

## Tests

- Owner mark paid / completed / cancel inside the window → refused; after the window → cancel expires the payment and cancels the group.
- Owner settlement of a provider-linked payment → refused.
- Owner-settled payment + provider success → one refund owed, owner notified, critical log; a second identical webhook → no second refund.
- Online success + repeated webhook → still `AlreadyFinal`, no refund.
- Walk-in and free bookings unchanged.
- MySQL/MariaDB: owner action and webhook racing on the same payment (tenant-first lock order).

## Docs to update

`docs/domains/payment.md`, `docs/domains/booking.md` (owner actions), `docs/requirements.md` FR-BOOKING-009/014 wording (Tech Lead), BQ-049.

## Evidence (fill when done)

_Fill in when done._

Disetujui Tech Lead 2026-11-04 untuk sesi agen.

Blocked in this session: Plan 2 removes provider-linked owner settlement, directly contradicting Accepted ADR-0004 decision 6. No application change made; BQ-049 remains open with BQ-087 until the accepted contract is amended.
