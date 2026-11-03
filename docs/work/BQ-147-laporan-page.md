---
id: BQ-147
title: Laporan: summary, revenue, occupancy, services and customers
type: feature
status: proposed
priority: P1
phase: 4
domain: insights
requirements: [FR-ANALYTICS-007]
invariants: [INV-SUB-01]
lane: light
approval: none
decision: D-007, D-013, D-017
launch-blocker: true
depends-on: [BQ-111, BQ-130]
---

# BQ-147: Laporan: summary, revenue, occupancy, services and customers

Epic **E5 · Medium value** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Analytics is a locked placeholder and the schedule report is a separate locked page.

## Plan

1. Build `owner-portal.md` §8.13 with one period filter and five tabs; `/owner/schedule-report` redirects.
2. Exports per tab, formula-safe, Medium and up.

## Scope

- **Acceptance criteria owned:** AC-LAP-01, AC-LAP-02, AC-LAP-03, AC-LAP-04.
- **New requirements owned:** FR-ANALYTICS-007.
- **ADRs:** none.

## Invariants at risk

Figures from payments and bookings; INV-SUB-01 for the plan gate.

## Tests

- AC-LAP-01 to AC-LAP-04.

## Docs to update

- `docs/domains/insights.md`

## Evidence (fill when done)
