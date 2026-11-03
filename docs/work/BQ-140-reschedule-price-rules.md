---
id: BQ-140
title: Reschedule price rules: customers same or cheaper, owners any slot with the difference recorded
type: feature
status: proposed
priority: P1
phase: 4
domain: booking
requirements: []
invariants: [INV-BOOK-03]
lane: heavy
approval: tech-lead
decision: D-006
launch-blocker: true
depends-on: [BQ-113, BQ-120, BQ-135]
---

# BQ-140: Reschedule price rules: customers same or cheaper, owners any slot with the difference recorded

Epic **E3 · Approved decisions D-001, D-002, D-006** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Reschedule ignores price differences entirely (BQ-109).

## Plan

1. Customer reschedule offers only slots with the same or a lower price; others show "Hubungi pemilik".
2. Owner reschedule shows and records the difference in the booking history; no money moves.
3. Carry every finding of BQ-109.

## Scope

- **Acceptance criteria owned:** AC-PNL-05.
- **New requirements owned:** none.
- **ADRs:** ADR-0005.
- **Absorbs:** BQ-109 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-BOOK-03/04 through `ClaimSlots`.

## Tests

- AC-PNL-05.
- Customer cannot pick a more expensive slot, also by posting its id.

## Docs to update

- `docs/domains/booking.md`

## Evidence (fill when done)
