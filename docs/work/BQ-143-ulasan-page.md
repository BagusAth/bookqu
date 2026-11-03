---
id: BQ-143
title: Ulasan page with tabs and suggested replies
type: feature
status: proposed
priority: P2
phase: 4
domain: customers-reviews
requirements: []
invariants: [INV-TEN-01]
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: [BQ-129]
---

# BQ-143: Ulasan page with tabs and suggested replies

Epic **E4 · Trust and acquisition pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Reviews load without a cap, interpolate search unescaped and use an ungoverned reply modal (BQ-101).

## Plan

1. Build `owner-portal.md` §8.11.
2. Carry every finding of BQ-101.

## Scope

- **Acceptance criteria owned:** AC-ULS-01, AC-ULS-02.
- **New requirements owned:** none.
- **ADRs:** none.
- **Absorbs:** BQ-101 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-TEN-01.

## Tests

- AC-ULS-01, AC-ULS-02.

## Docs to update

- `docs/domains/customers-reviews.md`

## Evidence (fill when done)
