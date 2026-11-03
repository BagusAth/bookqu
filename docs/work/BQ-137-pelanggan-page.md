---
id: BQ-137
title: Pelanggan: directory, quick filters and the customer panel
type: feature
status: proposed
priority: P2
phase: 8
domain: customers-reviews
requirements: []
invariants: [INV-TEN-01]
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: [BQ-129, BQ-131]
---

# BQ-137: Pelanggan: directory, quick filters and the customer panel

Epic **E2 · Core loop pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The customer page keeps identifiers in Alpine, interpolates search unescaped and has no phone view (BQ-100).

## Plan

1. Build `owner-portal.md` §8.5 with summary, quick filters, list and customer panel.
2. "Buat booking" prefills the walk-in form.
3. Carry every finding of BQ-100, including the escaping fixes.

## Scope

- **Acceptance criteria owned:** AC-PLG-01, AC-PLG-02, AC-PLG-03, AC-PLG-04.
- **New requirements owned:** none.
- **ADRs:** none.
- **Absorbs:** BQ-100 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-TEN-01 for customer identities and notes.

## Tests

- AC-PLG-01 to AC-PLG-04.
- Search input is escaped.

## Docs to update

- `docs/domains/customers-reviews.md`

## Evidence (fill when done)
