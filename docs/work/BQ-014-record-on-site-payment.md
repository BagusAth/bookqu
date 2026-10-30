---
id: BQ-014
title: Record on-site payment when the owner completes a pending booking
type: product-change
status: in-progress
priority: P2
phase: 4
domain: booking
requirements: [FR-BOOKING-009, BR-CORE-008]
invariants: [INV-BOOK-02, INV-PAY-01]
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-067]
---

# BQ-014: Record on-site payment when the owner completes a pending booking

## Problem

`BookingState` allows pending→completed (used when a customer pays on site). The booking becomes `completed` while its online payment stays `pending` and later expires to `gagal`, so financial records show a completed booking with a failed payment.

> Decided in ADR-0004 (decision 6): owner settlement applies to the whole payment group, records `sukses` with `metode = cash`, and requests gateway cancellation. Implemented inside BQ-067.

## Plan

Lead decides whether completing a pending booking must record a cash payment (e.g. mark the payment `sukses` with `metode = cash` and cancel the gateway link). Update requirements, then implement in `UpdateBookingStatus` with tests.

## Evidence

Implemented in BQ-067 as decided in ADR-0004 decision 6: completing or marking paid a pending booking settles the whole payment group as `sukses` with `metode = cash` through the Payment lifecycle, and gateway cancellation is requested (a logged no-op until BQ-002).
- `PaymentLifecycleTest::test_owner_completing_a_pending_booking_records_a_cash_payment` covers completing a pending booking.
- `test_owner_cannot_mark_paid_a_booking_whose_payment_failed` checks that a failed payment is not overwritten.
