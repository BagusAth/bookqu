---
id: BQ-146
title: Pengaturan → Tim: invitations, roles, revocation and the activity log
type: feature
status: proposed
priority: P1
phase: 8
domain: tenancy
requirements: [FR-TEAM-001, FR-TEAM-004, FR-TEAM-006]
invariants: [INV-SUB-01, INV-TEN-01]
lane: heavy
approval: tech-lead
decision: D-011, D-017
launch-blocker: true
depends-on: [BQ-131, BQ-130]
---

# BQ-146: Pengaturan → Tim: invitations, roles, revocation and the activity log

Epic **E5 · Medium value** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The Pemilik cannot invite anyone; actions are not attributed.

## Plan

1. Invitation, acceptance, role change and revocation (ADR-0016 decision 6) with seat limits from `EntitlementRules`.
2. Actor recorded by every mutating Action; activity log for the Pemilik.
3. Kasir cancellation request flow (FR-TEAM-006).
4. Remove the old staff module (ADR-0016 decision 9).

## Scope

- **Acceptance criteria owned:** AC-SET-05, AC-SET-06.
- **New requirements owned:** FR-TEAM-001, FR-TEAM-004, FR-TEAM-006.
- **ADRs:** ADR-0016.

## Invariants at risk

INV-TEN-01, INV-SUB-01.

## Tests

- AC-SET-05, AC-SET-06.
- Invitation expiry and single use.
- Actor present on every booking history entry.

## Docs to update

- `docs/domains/auth.md`
- `docs/domains/tenancy.md`

## Evidence (fill when done)
