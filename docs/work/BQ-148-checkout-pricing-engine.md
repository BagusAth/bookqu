---
id: BQ-148
title: One booking pricing rule with a frozen price breakdown
type: feature
status: in-progress
priority: P1
phase: 4
domain: booking
requirements: [FR-BOOKING-023]
invariants: [INV-PAY-04]
lane: heavy
approval: tech-lead
decision: D-014, D-015
launch-blocker: true
depends-on: [BQ-088, BQ-133]
---

# BQ-148: One booking pricing rule with a frozen price breakdown

Epic **E5 · Medium value** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Quote and charge are computed in several places, and nothing stores how an amount was made up.

## Plan

1. `BookingPricing` per ADR-0019 decision 1; draft quote equals charge.
2. Store the price breakdown per booking group; invoices, lists and reports read it.
3. Remove unused `voucher_code` and `selected_addons` until BQ-149 and BQ-150 use them.

## Scope

- **Acceptance criteria owned:** none (infrastructure for other items).
- **New requirements owned:** FR-BOOKING-023.
- **ADRs:** ADR-0019, ADR-0008.

## Invariants at risk

INV-PAY-04: provider amount equals the stored total.

## Tests

- Pricing rule order, rounding, quote equals charge, frozen amount after price changes.

## Docs to update

- `docs/domains/booking.md`
- `docs/domains/payment.md`

## Evidence (fill when done)

Disetujui Tech Lead 2026-11-07 (backend lebih dulu, tiga bundle: A = BQ-133 + BQ-148, B = BQ-151 + BQ-152 + BQ-153, C = BQ-149 + BQ-150 + BQ-140). UI menyusul setelah BQ-129.

Backend (bundle A, 2026-11-07): `BookingPricing::quote` (slot price × participants, then add-ons and promo, both empty until BQ-150/BQ-149) prices the checkout page, the checkout fingerprint, `CreateBooking` inside the lock and walk-ins. Placement stores the result in `payments.price_breakdown`; `jumlah` equals its total. `Booking::priceLabel` and the customer detail read the frozen line; older bookings keep the old label. `CreateBookingRequest` keeps refusing `voucher_code` and `selected_addons` (ADR-0019 decision 5) until BQ-149/BQ-150. Test: `PriceRulesAndOpeningHoursTest::test_storefront_placement_freezes_the_amount_against_later_price_changes`, `test_checkout_fingerprint_changes_when_a_price_rule_changes`, `SlotPricingTest::test_quote_sums_slot_prices_per_participant_and_keeps_zero_overrides`.

Remaining: owner lists, invoices and reports that still show current prices move to the breakdown with their pages (BQ-135, BQ-147). Status stays in-progress.

2026-11-13: Owner bookings and the shared panel read the payment and its frozen breakdown. Customer invoices show frozen slot amounts where present; older groups retain the transaction-level fallback. Verified by OwnerPortalWorkflowTest and BookingManageTest. Remaining: reports migrate with BQ-147.
