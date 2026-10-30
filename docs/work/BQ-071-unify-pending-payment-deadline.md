---
id: BQ-071
title: Derive slot hold, expiry sweep and links from one payment deadline
type: refactor
status: approved
priority: P0
phase: 4
domain: payment
requirements: [BR-PAY-001, FR-BOOKING-017]
invariants: [INV-BOOK-03, INV-PAY-04]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-067, BQ-032]
---

# BQ-071: Derive slot hold, expiry sweep and links from one payment deadline

Plan approved by agathan in [ADR-0009](../adr/ADR-0009-pending-payment-lifetime.md), 2026-10-30.

## Problem

Six deadlines govern one pending payment: provider link expiry (`config/payment.php` `expiry_minutes`, read for Midtrans too), subscription link (hard-coded 1 h ×2), slot hold (15 min from `bookings.created_at` in `BookingRules` and `EvictStalePendingBookings`), a 15-minute sweep, and no sweep at all for subscription payments. Raising the link expiry would make eviction cancel bookings that are still payable (guaranteed late successes).

## Plan

1. One deadline per payment in `payments.expired_at`, from one config value per purpose (booking 15 min, subscription 60 min) plus one settlement margin (2 min); provider links use the same instant.
2. `BookingRules` evaluates the pending hold from the payment deadline + margin instead of `created_at`; remove other copies.
3. Sweep booking and subscription payments every minute, one tenant per transaction, provider-first through the Outcome intake, then expiry through the Payment lifecycle; gateway cancel after commit.
4. Checkout countdowns read `expired_at`.

## Invariants at risk

INV-BOOK-03 (single occupancy rule) and INV-PAY-04 (success only from provider evidence). Characterize current eviction/expiry first.

## Tests

Frozen-time boundaries at `expired_at` and `expired_at + margin`; provider says `sukses` during sweep; `unknown` provider; subscription sweep; MySQL sweep vs claim (BQ-023).

## Docs to update

`docs/domains/payment.md`, `booking.md`, `schedule.md` (current state), `docs/operations.md` §4 (sweep cadence).

## Evidence (fill when done)

_Fill in when done._
