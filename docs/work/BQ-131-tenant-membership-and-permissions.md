---
id: BQ-131
title: Tenant membership, role permission map and owner policies
type: feature
status: proposed
priority: P1
phase: 4
domain: tenancy
requirements: [FR-TEAM-002, FR-TEAM-003, FR-TEAM-005]
invariants: [INV-TEN-01, INV-TEN-02]
lane: heavy
approval: tech-lead
decision: D-011
launch-blocker: true
depends-on: []
---

# BQ-131: Tenant membership, role permission map and owner policies

Epic **E1 · Foundations** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

A tenant has exactly one user, policies are missing for most owner resources (BQ-031), and nothing can express a role below "owner".

## Plan

1. Create `tenant_members` with a `pemilik` row per existing owner (ADR-0016 decision 1); `TenantMiddleware` resolves the tenant from the active membership on every request.
2. Add `App\Domain\Team\TenantPermission` with the FR-TEAM-002 matrix; add Policies for every owner resource that check tenant ownership and the ability.
3. Revenue figures become the `revenue.view` ability (ADR-0016 decision 8); notifications are filtered by role (FR-TEAM-005).
4. No invitation UI yet (BQ-146); only the Pemilik exists until then.

## Scope

- **Acceptance criteria owned:** none (infrastructure for other items).
- **New requirements owned:** FR-TEAM-002, FR-TEAM-003, FR-TEAM-005.
- **ADRs:** ADR-0016, ADR-0010.
- **Absorbs:** BQ-031 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-TEN-01, INV-TEN-02: tenant and role only from the membership; foreign ids stay 404.

## Tests

- Per role: allowed and refused abilities for every owner route.
- Revoked membership is refused at the next request.
- Owner A cannot reach tenant B.

## Docs to update

- `docs/domains/auth.md`
- `docs/domains/tenancy.md`

## Evidence (fill when done)
