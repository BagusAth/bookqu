---
id: BQ-134
title: Kalender: day, week and month views with walk-in and slot actions
type: feature
status: proposed
priority: P1
phase: 8
domain: schedule
requirements: []
invariants: [INV-BOOK-03]
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: [BQ-129, BQ-133]
---

# BQ-134: Kalender: day, week and month views with walk-in and slot actions

Epic **E2 · Core loop pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The calendar has its own booking modal, cramped grids and month navigation that skips or repeats months (BQ-117).

## Plan

1. Build `owner-portal.md` §8.2: views, filters, now line, phone list view.
2. Booking clicks open the shared panel; free slots offer walk-in, close and price change through the Jam Buka & Harga actions.
3. Fix month navigation (carry BQ-117).

## Scope

- **Acceptance criteria owned:** AC-KAL-01, AC-KAL-02, AC-KAL-03, AC-KAL-04.
- **New requirements owned:** none.
- **ADRs:** none.
- **Absorbs:** BQ-117 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-BOOK-03: a slot with an active booking cannot be closed.

## Tests

- AC-KAL-01 to AC-KAL-04.
- Month navigation across month ends and leap years.

## Docs to update

- `docs/domains/schedule.md`

## Evidence (fill when done)
