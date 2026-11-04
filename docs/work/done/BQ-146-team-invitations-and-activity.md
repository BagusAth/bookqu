---
id: BQ-146
title: Pengaturan → Tim: invitations, roles, revocation and the activity log
type: feature
status: done
priority: P1
phase: 4
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

Epic **E5 · Medium value** of the owner portal redesign, planned in [BQ-126](../BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

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

Disetujui Tech Lead 2026-11-04 untuk sesi agen.

AC-SET-05/06 and FR-TEAM-001/004/006 verified by TeamInvitationTest (seven scenarios), including role change, session invalidation, single use, expiry, attribution, and cancellation decisions. TeamSeatLockingTest verifies real MySQL tenant locks on issue and acceptance. Staff endpoints and writes retired; historical tables retained.
SQLite: 710 passed (4077 assertions). MySQL group: 14 passed (54 assertions). Pint and PHPStan passed; one obsolete baseline entry removed. Evidence: PR #39.

Merged into `integration` through PR #39 (merge commit `0f92e68`, 2026-11-04); four CI jobs green on `f03cb61`.
