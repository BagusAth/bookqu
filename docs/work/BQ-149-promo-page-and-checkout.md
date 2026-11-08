---
id: BQ-149
title: Promo page and promo codes in checkout
type: feature
status: approved
priority: P1
phase: 4
domain: booking
requirements: []
invariants: [INV-PAY-03]
lane: heavy
approval: tech-lead
decision: D-015
launch-blocker: true
depends-on: [BQ-148, BQ-130]
---

# BQ-149: Promo page and promo codes in checkout

Epic **E5 · Medium value** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Vouchers exist as a hidden module and the checkout endpoint always answers 422.

## Plan

1. Build `owner-portal.md` §8.10; `/owner/vouchers` redirects.
2. Promo validation and quota reservation per ADR-0019 decision 3.

## Scope

- **Acceptance criteria owned:** AC-PRM-01, AC-PRM-02, AC-PRM-03, AC-PRM-04.
- **New requirements owned:** none.
- **ADRs:** ADR-0019.

## Invariants at risk

INV-PAY-03/04; quota exact under concurrency.

## Tests

- AC-PRM-01 to AC-PRM-04.
- MySQL concurrency for the last use.

## Docs to update

- `docs/domains/booking.md`

## Evidence (fill when done)

Disetujui Tech Lead 2026-11-07 (backend lebih dulu, tiga bundle: A = BQ-133 + BQ-148, B = BQ-151 + BQ-152 + BQ-153, C = BQ-149 + BQ-150 + BQ-140). UI menyusul setelah BQ-129.
