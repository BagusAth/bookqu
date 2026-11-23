---
id: BQ-071
title: Derive slot hold, expiry sweep and links from one payment deadline
type: refactor
status: done
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

Plan approved by agathan in [ADR-0009](../../adr/ADR-0009-pending-payment-lifetime.md), 2026-10-30.

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

Built on branch `bq-071-one-payment-deadline` (2026-10-30).

**Steps 1–4 of the plan:**
1. **One deadline.** `config/payment.php` → `deadline` (`booking_minutes` 15, `subscription_minutes` 60, `settlement_margin_minutes` 2; envs in `.env.example`).
   - `PaymentRules::deadlineFor()` sets `payments.expired_at` in `CreateBookingPayment` and `CreateSubscriptionPayment`. The subscription window is no longer a hard-coded hour.
   - Midtrans Snap and SingaPay links expire at that instant (`minutesUntilDeadline`, `expired_at`). The per-driver `expiry_minutes`/`subscription_expiry_hours` keys and `services.singapay.expiry_minutes` are removed.
2. **Slot hold.** `BookingRules::applyOccupiesSlotCondition` holds a pending booking while its payment is `pending` and `expired_at > now − margin`. Legacy pending bookings whose payment has no deadline keep `created_at` + window + margin.
   - Removed copies: `PENDING_GRACE_MINUTES`, `BookingState::occupiesSlot`, `AvailabilityRules::isPendingBookingHoldingSlot`, and the hard-coded 15 minutes in `Customer/BookingController` and `BookingManageController`. The `slot-occupancy` allowlist shrank by 2.
   - `EvictStalePendingBookings` selects stale bookings with the same rule.
3. **Sweep.** `bookings:expire-payments` runs every minute over booking **and** subscription payments past `expired_at` + margin, ordered by tenant. Each payment goes through `PaymentOutcomeIntake::check()`, which asks the provider first, then expires in the lifecycle's tenant-locked transaction; gateway cancel runs after commit.
   - `check()` and the customer and owner payment pages use the same margin. A success that arrives within the margin still settles.
4. **Countdowns.** The checkout countdowns already read `expired_at`.

**Tests:**
- `RF09StabilityAndHardeningTest::test_grace_period_boundary_semantics` now freezes the new boundaries: deadline ahead, within margin, one second before the end of the margin, margin over, long past, payment failed. `test_p0_04_active_booking_definition_semantics` covers paid, completed and cancelled.
- `PaymentOutcomeIntakeTest`:
  - `test_check_waits_for_the_settlement_margin_after_the_deadline`;
  - `test_sweep_reclaims_booking_and_subscription_payments_after_the_margin` (provider asked first; a payment within the margin is left alone);
  - `test_check_asks_the_provider_before_expiring` (provider `sukses` past the deadline settles).
- The unit tests of the two deleted helpers were removed; the occupancy rule is covered through `BookingRules`.

**Not done here:** the MySQL "sweep vs claim" concurrency test. It needs the BQ-012 claim path, so it moves to BQ-012.

**Local runs:** SQLite suite 464 tests pass; Pint and Larastan clean (baseline lost 12 lines). GitHub Actions run https://github.com/BagusAth/bookqu/actions/runs/41091607393 (PR #6): Quality checks, SQLite, MySQL and MariaDB passed.
