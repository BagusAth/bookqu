---
id: BQ-148
title: One booking pricing rule with a frozen price breakdown
type: feature
status: proposed
priority: P1
phase: 8
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
