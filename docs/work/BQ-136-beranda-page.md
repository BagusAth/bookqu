---
id: BQ-136
title: Beranda: today, needs action and the new owner guide
type: feature
status: proposed
priority: P1
phase: 4
domain: insights
requirements: [FR-DASH-008]
invariants: []
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: [BQ-129, BQ-131, BQ-089]
---

# BQ-136: Beranda: today, needs action and the new owner guide

Epic **E2 · Core loop pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The dashboard shows charts, truncated and misleading figures (BQ-094) and renders live data as HTML (BQ-089).

## Plan

1. Build `owner-portal.md` §8.1: one banner by priority, four figures, needs action, today and tomorrow, link card, quick actions, new owner guide.
2. Polling only while visible; text rendering only.
3. Move charts to Laporan (BQ-147); carry every finding of BQ-094.

## Scope

- **Acceptance criteria owned:** AC-BRD-01, AC-BRD-02, AC-BRD-03, AC-BRD-04, AC-BRD-05, AC-BRD-06, AC-BRD-07.
- **New requirements owned:** FR-DASH-008.
- **ADRs:** none.
- **Absorbs:** BQ-094 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

Figures from payments and bookings of the current tenant only.

## Tests

- AC-BRD-01 to AC-BRD-07.

## Docs to update

- `docs/domains/insights.md`

## Evidence (fill when done)
