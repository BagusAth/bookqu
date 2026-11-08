---
id: BQ-152
title: Unit page and unit conflicts across services
type: feature
status: approved
priority: P1
phase: 4
domain: booking
requirements: [FR-UNIT-001, FR-UNIT-002, FR-UNIT-003, FR-UNIT-004]
invariants: [INV-BOOK-03]
lane: heavy
approval: tech-lead
decision: D-012
launch-blocker: true
depends-on: [BQ-151, BQ-130]
---

# BQ-152: Unit page and unit conflicts across services

Epic **E6 · Pro value and core rules** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Services that share a court can be booked at the same time.

## Plan

1. `units` and `service_unit` from `resources` (ADR-0017 decision 4); Unit page of `owner-portal.md` §8.7; unit field in Layanan; Kalender filter.
2. Unit conflict in `BookingRules` inside the claim; existing conflicts listed.

## Scope

- **Acceptance criteria owned:** AC-UNT-01, AC-UNT-02, AC-UNT-03.
- **New requirements owned:** FR-UNIT-001, FR-UNIT-002, FR-UNIT-003, FR-UNIT-004.
- **ADRs:** ADR-0017.

## Invariants at risk

INV-BOOK-03/04.

## Tests

- AC-UNT-01 to AC-UNT-03.
- MySQL: same unit from different services.

## Docs to update

- `docs/domains/booking.md`

## Evidence (fill when done)

Disetujui Tech Lead 2026-11-07 (backend lebih dulu, tiga bundle: A = BQ-133 + BQ-148, B = BQ-151 + BQ-152 + BQ-153, C = BQ-149 + BQ-150 + BQ-140). UI menyusul setelah BQ-129.
