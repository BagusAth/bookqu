# ADR-0024: Every owner portal module is released; legacy paths are removed

> **Status:** Accepted
> **Date:** 2026-11-16
> **Decider:** Tech Lead and Product Lead (both agreed 2026-11-16)
> **Supersedes:** the "redesign capabilities stay hidden until audited" part of [ADR-0021](ADR-0021-release-gating-after-the-owner-portal-redesign.md) and the Tier 2/3 table of [ADR-0002](ADR-0002-modular-release-staging-and-ui-gating.md)

## Context

ADR-0021 kept every redesign capability `hidden` in `config/modules.php` until its implementation was audited. Every item of epics E0–E6 is now done and BQ-157 removed the withdrawn modules (staff, resources, direct payment). The Tech Lead and the Product Lead want to test the whole portal on `integration` and staging before the first production release (BQ-158).

Some routes of the old portal remain beside their redesigned replacements: write routes under `/owner/programs`, `/owner/vouchers` and `/owner/additional-items`, and the registry keys `vouchers`, `additional-items` and `assets`.

## Options

1. Keep the modules hidden and release them per environment through `MODULE_*` env values. Rejected: `integration` and staging would differ from the code under review, and every tester needs its own configuration.
2. Release every module in the registry, keep the module gate as a per-environment emergency switch, and remove the legacy duplicates. **Chosen.**

## Decision

1. Every module in `config/modules.php` defaults to `released`: `units`, `capacity`, `no-show`, `promos`, `add-ons`, `team`, `booking-page`, `reports`, `refunds`, `help`, `api`, `integrations` and `balance`. Each reads `MODULE_<NAME>`, so an environment can still hide or lock one in an emergency (ADR-0011 semantics unchanged).
2. Plan limits do not change: `EntitlementRules` and `config/plans.php` still decide which plan may use a capability (INV-SUB-01). Role limits stay in `TenantPermission` (ADR-0016).
3. Legacy duplicates are removed: the `vouchers`, `additional-items` and `assets` registry keys, the write routes of `/owner/programs`, `/owner/vouchers` and `/owner/additional-items`, and the code paths that chose between the old and new route family. The old GET paths keep their redirects from `owner-portal.md` §10.2 (`/owner/vouchers` → Promo, `/owner/additional-items` → Layanan → Add-on, `/owner/programs` → Layanan).
4. Production still receives this only through BQ-158 with the Product Lead's "go" (D-018).

## Consequences

- The sidebar shows every page the plan and role allow; tests that asserted hidden modules now assert the released state, and tests that need a hidden module set it explicitly.
- `AGENTS.md` rule 13 and `docs/product.md` §8 describe the registry as an emergency switch instead of release tiers.
- Laporan runs the unit and promo metrics by default (two more grouped queries).
