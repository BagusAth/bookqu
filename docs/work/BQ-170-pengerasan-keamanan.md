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

The Tech Lead approved the scope in the task instructions on 2026-11-21. The local branch uses hyphens because the existing `agathan` ref prevents a branch with that prefix and a slash.

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

- `ee9b1b0`: five current-password requests per minute per authenticated user, shared across the five routes. The prior payout throttle stays in place. Another user keeps a separate budget.
- `b32b01f`: BQ-006 already returned 403 without a tenant. The remaining owner-relation fallback is removed. The route inventory found 33 gated routes: 4 add-ons, 1 analytics, 7 owner API settings, 6 integrations, 5 landing-page/custom-domain, 6 promos and 4 units. Each gated route has tenant middleware. Login, registration and tenantless owner onboarding still work.
- `986cfe4`: nulls orphaned payout processor IDs and logs the count before adding the foreign key. The migration test checks the logged count, valid references, nulls, invalid updates and SET NULL on deletion.
- Audit fixes: `62775c6` excludes the deletion password from validation flash, `fea46ed` throttles the Google Calendar OAuth callback, and `2716434` denies an empty role gate. Login, registration, password reset, invitation exchange and payout confirmation already have throttles.
- Follow-up: provider webhook ingress and invalid API-key attempts need a separate edge and application rate-limit design. Review retry contracts, provider source traffic, proxy behavior and shared-IP limits before applying a blanket throttle. The valid API-key path already has a per-key budget.
- Verification: Pint and PHPStan passed on PHP 8.4.25 with CLI OPcache disabled for PHPStan. Focused checks passed for account/payout confirmation (26 tests), subscription/route gating (33), payout and migration (18), and audit changes (26).
- SQLite: 988 passed, 6,582 assertions. MySQL: 1,007 total, 6,650 assertions, one SQLite-only regression skipped. The new migration regression ran in both suites.
