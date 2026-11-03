# ADR-0021: Release, plan and role gating after the owner portal redesign

> **Status:** Accepted
> **Date:** 2026-11-04
> **Decider:** Tech Lead (accepted 2026-11-04) — Product Lead for the release policy and plan contents (D-017, D-018)
> **Work item:** BQ-126 (specification); implementation items follow in epic E7

## Context

ADR-0002 introduced release tiers. ADR-0011 enforces them on the server through `config/modules.php` and the `module:<name>` middleware, and keeps entitlement separate (`EntitlementRules`, INV-SUB-01).

The redesign changes what the tiers protect:

| Decision | Effect on gating |
|---|---|
| D-018 | Production is released only after every capability of the redesign is built, so there is no production period with locked or hidden owner modules |
| D-017 | Defines what each plan contains (`product.md` §7) |
| D-011 | Adds roles, a third gate (ADR-0016) |
| D-016 | Removes the direct-payment module |
| D-011 | Removes the staff module |
| D-007 Revisi 1 | Defines fifteen menus (`owner-portal.md` §4) |

Today navigation, plan lists (`config/plans.php`), the landing page and module states are maintained separately, which allowed the plan list to promise features that do not exist.

## Options

1. **Remove the module registry once everything is released.** Development of unfinished pages on `integration` would leak into staging and into any hotfix release from `main`.
2. **Keep the registry as a development gate, add the plan and role gates beside it, and build navigation and plan lists from one capability map.** Chosen.

## Decision

1. **Three gates, in order, all on the server, all fail closed:**
   1. **module** (is the capability built and released, `module:<name>`, ADR-0011);
   2. **entitlement** (does the plan include it, `subscription:<feature>`, `EntitlementRules`);
   3. **permission** (may this role do it, `TenantPermission`, ADR-0016).

   A route group declares the gates it needs. No view or controller re-decides any of them.
2. **The registry becomes a development gate.**
   - Each redesign capability gets a module entry, `hidden` until its work items are done:
     - `units`, `promos`, `add-ons`, `team`;
     - `booking-page`, `landing-page`;
     - `integrations`, `api`;
     - `reports`, `refunds`, `help`.
   - The first production release (D-018) ships with every entry `released`.
   - After that, the registry is used only to stage new capabilities on `integration` and staging.
   - `locked` is no longer used for owner modules. A plan-gated capability shows the upgrade prompt through the entitlement gate instead (`owner-portal.md` §3 rule 6).
3. **Removed modules are deleted, not hidden:**
   - `direct-payment`: routes, views, the `payment_mode` = `owner` path and the stored `midtrans_*` tenant credentials (D-016);
   - `staff-resources`: replaced by `units` and `team`;
   - the old `integrations` page;
   - the old `schedule-report` route, which redirects to Laporan.
4. **One capability map.**
   - `config/plans.php` becomes the single list of plan capabilities and limits, keyed by the entitlement feature names that `EntitlementRules` reads.
   - It also holds the user-facing feature text.
   - The owner plan comparison and the platform landing page render from it, so they cannot promise what does not exist (FR-SUB-014).
   - Prices and the numeric limits stay in the `plans` table until a separate migration item moves them.
5. **One navigation definition.**
   - The fifteen menus of `owner-portal.md` §4 are declared once, each with its module, feature and ability.
   - The sidebar, the bottom navigation and the "Lainnya" sheet filter that definition through the three gates.
   - `OwnerSidebarNavigationTest` asserts the menus per plan and per role.
6. **Supersedes** ADR-0002 (tier table and the Tier 2 "locked" display for owner modules) and ADR-0011 decision 1 (the `locked` meaning for owner modules). ADR-0011 decisions 2 to 5 stand.

## Consequences

- **Release:** releasing production is a configuration change of the registry, plus the release steps of ADR-0015. It is approved by the Product Lead (D-018).
- **`AGENTS.md` rule 13** refers to this ADR.
- **`product.md` §8** describes the target, and the current table stays until the release.
- **Tests:**
  - every redesign route group is covered by a gate test (hidden module → 404, missing feature → upgrade response, missing ability → 403);
  - the plan comparison test reads `config/plans.php`.
- **`docs/domains/staged-modules.md`** changes with the implementation.
