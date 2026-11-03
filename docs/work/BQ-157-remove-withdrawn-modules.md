---
id: BQ-157
title: Remove withdrawn modules, stored gateway credentials and duplicate paths
type: refactor
status: proposed
priority: P1
phase: 5
domain: staged-modules
requirements: []
invariants: []
lane: heavy
approval: tech-lead
decision: D-011, D-016
launch-blocker: true
depends-on: [BQ-141, BQ-146, BQ-152]
---

# BQ-157: Remove withdrawn modules, stored gateway credentials and duplicate paths

Epic **E7 · Release** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Hidden modules and fake controls remain in the code (BQ-097); tenant Midtrans credentials are stored in `tenants`.

## Plan

1. Delete the direct-payment module and the `owner` payment mode; erase stored `midtrans_*` values in a migration.
2. Delete the staff module after BQ-146; old integrations page; redirects of `owner-portal.md` §10.2.
3. Carry every finding of BQ-097.

## Scope

- **Acceptance criteria owned:** none (infrastructure for other items).
- **New requirements owned:** none.
- **ADRs:** ADR-0021.
- **Absorbs:** BQ-097 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

No tenant can reach another tenant through a removed path.

## Tests

- Every old path redirects or 404s as specified; no credential column remains filled.

## Docs to update

- `docs/domains/staged-modules.md`
- `docs/domains/payment.md`

## Evidence (fill when done)
