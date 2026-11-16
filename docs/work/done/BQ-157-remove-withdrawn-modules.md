---
id: BQ-157
title: Remove withdrawn modules, stored gateway credentials and duplicate paths
type: refactor
status: done
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

Epic **E7 · Release** of the owner portal redesign, planned in [BQ-126](../BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

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

Approved and closed 2026-11-16 (Tech Lead). PR #61 (`agathan-remove-withdrawn`): staff and resources removed (models, policies, routes, views, four tables; resource rows already copied to units), the owner payment mode and stored tenant Midtrans keys removed and erased by migration, unused `config/modules.php` keys removed, §10.2 redirects for the removed payment settings and `/owner/staff-resources`. BQ-097 findings checked: the notification toggles, fake pricing panel, staff/resource UI, Midtrans link and staff quota bar are no longer in the released views. Tests: `OwnerModulesCrudTest::test_retired_staff_and_resource_routes_are_gone`, `PlatformPaymentModeMigrationTest::test_bq157_erases_stored_tenant_gateway_credentials`, `PembayaranPageTest::test_ac_pay_05_*`. Left as is: `/owner/settings/integrations` stays the BQ-155 page instead of the §10.2 redirect; the staff limits in `PlanCapability`/`EntitlementRules` stay as plan data.
