---
id: BQ-109
title: Price discrepancy and payment adjustment on booking reschedule
type: bug-fix
status: rejected
priority: P1
phase: 8
domain: booking
requirements: [FR-BOOKING-011, FR-BOOKING-012, FR-PAYMENT-001, BR-CORE-008]
invariants: [INV-BOOK-02, INV-PAY-01]
lane: heavy
approval: tech-lead
decision: D-006
launch-blocker: false
depends-on: []
---

# BQ-109: Price discrepancy and payment adjustment on booking reschedule

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-140](../BQ-140-reschedule-price-rules.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-140.


Found during deep audit of reschedule flows (`RescheduleBooking.php` and `OwnerBookingController@reschedule`) on 2026-11-02.

> **Waits for D-006** (`docs/product-decisions.md`): how a price difference on reschedule is handled (extra payment, partial refund, only equal-priced slots, or ignored) is a product decision. The detection and logging below can be built first; the customer-facing rule follows the answer. (Tech Lead review, 2026-11-02.)

## Problem

### 1. Silent Price Discrepancy During Rescheduling (Price Drift)
In `app/Actions/Booking/RescheduleBooking.php:123-130`:
- When a booking is rescheduled to a new slot (by customer or by owner), the action updates `idschedule`, `tanggalbooking`, and `jam`.
- **It does not evaluate price differences between the original slot and the target slot.**
- For example:
  - An original booking was made on a weekday for Rp 100.000.
  - The new target slot falls on a weekend with a +50% surge configuration (`harga_override = 150000`) or has an individual slot price override.
  - The reschedule succeeds without adjusting the payment amount, issuing a top-up invoice, or alerting the owner that the customer received a higher-priced slot for the lower original fee.
  - Conversely, when moving from a surge slot to a regular slot, no price reconciliation is recorded.

### 2. Disconnect Between Payment and Booking Pricing (BR-CORE-008, INV-PAY-01)
`payments.jumlah` records the original amount charged. Because Bookings store no separate price column (relying on `payments.jumlah` or `schedules.harga_override`), a rescheduled booking with different slot pricing displays inconsistent amounts across analytics, financial reports, and invoice receipts.

## Plan

1. In `RescheduleBooking.php`, calculate the target slot's price (`$newSchedule->harga_override ?? $service->harga`) and compare it against the original paid amount.
2. For owner-initiated reschedules:
   - Provide visual notice in the reschedule modal showing whether there is a price difference.
   - If the new slot has a different price, record the difference in `booking_logs` and update financial audit metadata.
3. For customer-initiated reschedules (storefront / access grant flow):
   - Restrict automatic customer rescheduling to equal-priced slots, or prompt for contact with business if price differs, ensuring no unauthorized upgrades.

## Invariants at risk

`INV-BOOK-02` (state transitions) and `INV-PAY-01` (separation of payment and booking states).

## Tests

- Feature test: rescheduling a weekday booking to a surge weekend slot correctly detects price discrepancy.
- Feature test: `booking_logs` records the price differential on reschedule.
- Feature test: analytics and reports do not corrupt financial ledger totals following a rescheduled slot.

## Docs to update

`docs/domains/booking.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
