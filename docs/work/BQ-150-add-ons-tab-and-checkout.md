---
id: BQ-150
title: Layanan → Add-on tab and add-ons in checkout
type: feature
status: in-progress
priority: P1
phase: 4
domain: catalog
requirements: []
invariants: [INV-PAY-04]
lane: heavy
approval: tech-lead
decision: D-015
launch-blocker: true
depends-on: [BQ-148, BQ-132]
---

# BQ-150: Layanan → Add-on tab and add-ons in checkout

Epic **E5 · Medium value** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Additional items exist as a hidden module and are never charged.

## Plan

1. Build the Add-on tab of `owner-portal.md` §8.6 (stock or unlimited).
2. Add-on step in checkout and walk-in; stock reservation per ADR-0019 decision 3.

## Scope

- **Acceptance criteria owned:** AC-LYN-04, AC-LYN-05.
- **New requirements owned:** none.
- **ADRs:** ADR-0019.

## Invariants at risk

INV-PAY-04; stock exact under concurrency.

## Tests

- AC-LYN-04, AC-LYN-05.
- MySQL concurrency for the last unit.

## Docs to update

- `docs/domains/catalog.md`
- `docs/domains/booking.md`

## Evidence (fill when done)

Disetujui Tech Lead 2026-11-07 (backend lebih dulu, tiga bundle: A = BQ-133 + BQ-148, B = BQ-151 + BQ-152 + BQ-153, C = BQ-149 + BQ-150 + BQ-140). UI menyusul setelah BQ-129.

Bundle C backend: linked tenant add-ons contribute to the signed checkout quote and walk-in amount. Pending payments reserve finite stock; settlement consumes it and failure releases it. Evidence: CheckoutPricingTest, CheckoutPricingLockingTest. Layanan tab and checkout/walk-in UI remain.
