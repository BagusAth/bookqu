---
id: BQ-131
title: Tenant membership, role permission map and owner policies
type: feature
status: done
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

Epic **E1 · Foundations** of the owner portal redesign, planned in [BQ-126](../BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

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

Disetujui Tech Lead 2026-11-04 untuk sesi agen.

Implementation evidence (2026-11-04): membership schema and owner backfill; new owners receive an accepted pemilik membership. Resolver re-reads accepted/non-revoked memberships, denies ambiguous ownership and checks pemilik against tenants.iduser. TenantPermission maps roles, every owner route and notification types; permission middleware and 18 resource Policies enforce tenant/ability checks, with 404 for foreign records. Cashier dashboard/customer responses remove revenue/spending before rendering/JSON; own-account forms use the member identity and omit business/delete controls. Notification delivery, feed, mutations and unread counts filter by ability; cashier booking notifications contain no amount. No invitations or activity UI (BQ-146).

Tests: 9 new feature tests (matrix, every route/gate, revocation, forged cancellation, 18 foreign-resource policies, revenue omission, own identity, notification feed/delivery) plus one MySQL migration-backfill test. Existing subscription foreign-payment expectation amended from 403 to the explicitly required 404 (ADR-0010/0016); no assertion was weakened to preserve a bug.
Verification: full SQLite 690 passed / 3988 assertions; MySQL group 12 passed / 45 assertions; Pint fixed changed PHP; PHPStan no errors, baseline unchanged. Status remains in-progress until merge.

Out-of-scope finding: the legacy payments token-removal down migration fails on SQLite because a unique index still references manage_token when dropping the column. Discovered by an initial DatabaseMigrations teardown; the backfill test uses the existing MySQL DatabaseTruncation pattern and verifies this migration without exercising unrelated historical rollbacks. Legacy onboarding atomicity remains BQ-112.

Merged into `integration` through PR #39 (merge commit `0f92e68`, 2026-11-04); four CI jobs green on `f03cb61`.
