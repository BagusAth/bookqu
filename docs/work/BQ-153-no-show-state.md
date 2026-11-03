---
id: BQ-153
title: No-show booking state
type: feature
status: proposed
priority: P1
phase: 8
domain: booking
requirements: [FR-BOOKING-020]
invariants: [INV-BOOK-01]
lane: heavy
approval: tech-lead
decision: D-013
launch-blocker: true
depends-on: [BQ-151, BQ-135]
---

# BQ-153: No-show booking state

Epic **E6 · Pro value and core rules** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

A customer who did not come can only be marked completed.

## Plan

1. Add `no_show` per ADR-0018; `MarkBookingNoShow` Action; panel action; labels; reports count it.

## Scope

- **Acceptance criteria owned:** none (infrastructure for other items).
- **New requirements owned:** FR-BOOKING-020.
- **ADRs:** ADR-0018.

## Invariants at risk

INV-BOOK-01/02.

## Tests

- Every case listed in ADR-0018 Consequences.

## Docs to update

- `docs/domains/booking.md`

## Evidence (fill when done)
