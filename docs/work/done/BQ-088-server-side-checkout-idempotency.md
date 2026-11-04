---
id: BQ-088
title: Make storefront checkout idempotent on the server
type: improvement
status: done
priority: P2
phase: 4
domain: booking
requirements: [FR-BOOKING-005, FR-BOOKING-018]
invariants: [INV-BOOK-04]
lane: heavy
approval: tech-lead
launch-blocker: true
depends-on: []
---

# BQ-088: Make storefront checkout idempotent on the server

## Problem

The checkout page disables its submit buttons after the first click (`resources/views/customer/booking/checkout.blade.php:144-161`), but the server has no guard. Two checkout POSTs can still arrive together: a double tap before the script runs, a browser retry on a slow network, or two tabs.

What happens today (`app/Http/Controllers/Customer/BookingController.php`, `processCheckout`):

1. Both requests read the same `booking` session data.
2. The first claims the slots and creates the payment.
3. The second fails in `ClaimSlots` because the slots are now held by the first, and redirects to the schedule step with "slot sudah terisi", although the hold is the customer's own.
4. The browser shows the second response, so the customer never reaches the payment page of the first.
5. The session is written by both requests; the last write wins and can drop the first request's `booking_checkout` entry (`StorefrontCheckoutState::remember`), which hides the "Lanjutkan Pembayaran" banner.

No double booking or double charge is possible (INV-BOOK-04 holds), but the customer is confused and the slot stays held until the payment deadline.

## Plan

1. One checkout attempt per session at a time: an atomic cache lock keyed by the session id around `processCheckout`.
2. A request that finds a pending checkout already created for the same session, service, date and schedules (same fingerprint) redirects to that payment instead of claiming again.
3. Keep the browser guard; the server guard is the authority.

## Invariants at risk

INV-BOOK-04 and INV-BOOK-05 keep protecting the slots; the lock only removes the confusing second result. The lock must be released on every path, including exceptions.

## Tests

- Second checkout with the same fingerprint while the first is pending → redirected to the first payment, no second claim.
- Different selection after a pending checkout → normal behaviour (pending banner rules from BQ-083).
- Lock released after an exception.

## Docs to update

`docs/domains/booking.md`.

## Evidence (fill when done)

_Fill in when done._

Disetujui Tech Lead 2026-11-04 untuk sesi agen.

Implementation evidence (2026-11-04): Laravel 13 session blocking serializes checkout before reading the session and keeps the lock through persistence, with framework finally-release. `ResumeCheckout` matches the server-stored quote fingerprint and pending tenant payment before issuing a fresh Checkout handoff (no raw credential in session). Different slot selections are never overwritten by a replay. Three regressions prove same-payment replay (initially redirected home), distinct selections, and lock held during creation/released on exception. Framework docs checked through Boost search-docs.
Verification: full SQLite 681 passed / 3594 assertions; Pint fixed changed PHP; PHPStan no errors; MySQL group 11 passed / 43 assertions. No dependency or schema change.

Merged into `integration` through PR #39 (merge commit `0f92e68`, 2026-11-04); four CI jobs green on `f03cb61`.
