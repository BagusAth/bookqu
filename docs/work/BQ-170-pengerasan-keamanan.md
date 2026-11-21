---
id: BQ-170
title: Harden password confirmation, subscription gates and payout processing data
type: improvement
status: approved
priority: P1
phase: 4
domain: auth
requirements: [FR-AUTH-008, FR-SUB-005, FR-SUB-006]
invariants: [INV-TEN-01, INV-TEN-02, INV-SUB-01]
lane: heavy
approval: tech-lead
launch-blocker: false
depends-on: []
---

## Problem

Owner account and payout account actions confirm the signed-in user's password. Account actions lack a throttle; payout account mutations already have a stricter group throttle. Subscription middleware denies unresolved tenants under BQ-006 but still tries to infer a tenant from the signed-in owner's relation. A payout's processing user has no foreign key.

The Tech Lead approved the scope in the task instructions on 2026-11-21.

## Plan

1. Share a named per-user limiter across every route that validates the current password. Keep the existing payout account throttle.
2. Require the verified tenant context for every subscription feature gate. The current gated routes cover report export, units, add-on writes, promos and vouchers, landing and custom domain writes or previews, and integrations. They all sit in the owner tenant route group. Keep tenant-free authentication and onboarding outside this gate.
3. Null orphaned processing user IDs, log the aggregate count, and add a nullable foreign key to the users table with SET NULL on delete. Keep the valid references.
4. Review other authentication and owner endpoints that accept sensitive input, and middleware missing-context paths. Fix small confirmed gaps separately and record larger work here.

## Invariants at risk

The password budget is keyed by authenticated user and shared across confirmation endpoints. Tenant identity comes from the tenant middleware and subscription features use the existing entitlement rules. Payouts survive deletion of their processing user.

## Tests

Exercise all five password confirmation endpoints, the limit boundary, and independence of two users. Exercise missing and valid tenant context, plus the public and onboarding entry points. Run the payout migration regression on SQLite and MySQL with valid, null and orphaned processing user IDs. Run focused and full suites and PHPStan.

## Docs to update

Update the auth, subscription and balance-payout domains.

## Evidence

Pending implementation and verification.
