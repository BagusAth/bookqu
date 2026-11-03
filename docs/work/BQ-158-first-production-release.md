---
id: BQ-158
title: First production release of the redesigned BookQu
type: ops
status: proposed
priority: P0
phase: 5
domain: staged-modules
requirements: []
invariants: []
lane: heavy
approval: tech-lead
decision: D-018
launch-blocker: true
depends-on: [BQ-017, BQ-019, BQ-020, BQ-021, BQ-022, BQ-157]
---

# BQ-158: First production release of the redesigned BookQu

Epic **E7 · Release** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Production still runs the 2026-10-05 code; D-018 releases only when everything is built.

## Plan

1. Confirm every epic item is done and every launch blocker is closed.
2. Set every redesign module to `released`; run the release steps of ADR-0015 with backup and smoke tests.
3. Product Lead go/no-go (D-018).

## Scope

- **Acceptance criteria owned:** none (infrastructure for other items).
- **New requirements owned:** none.
- **ADRs:** ADR-0015, ADR-0021.

## Invariants at risk

All.

## Tests

- Full suite on MySQL and MariaDB; production smoke test of booking, payment, refund, payout.

## Docs to update

- `docs/operations.md`
- release notes

## Evidence (fill when done)
