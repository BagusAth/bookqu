---
id: BQ-130
title: Three server gates (module, plan, role) and one plan capability list
type: feature
status: in-progress
priority: P1
phase: 4
domain: staged-modules
requirements: [FR-SUB-014]
invariants: [INV-SUB-01]
lane: heavy
approval: tech-lead
decision: D-017, D-018
launch-blocker: true
depends-on: [BQ-131]
---

# BQ-130: Three server gates (module, plan, role) and one plan capability list

Epic **E1 · Foundations** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Module states, plan checks and plan lists are maintained separately; plan lists and the landing page promise features that do not exist (BQ-115, BQ-119); some entitlement checks bypass `EntitlementRules` (BQ-033).

## Plan

1. Add module entries for every redesign capability (ADR-0021 decision 2), hidden until their items are done.
2. Make `config/plans.php` the single capability list keyed by `EntitlementRules` feature names, with the plan contents of `product.md` §7; render the owner comparison and the landing page from it (landing prices from the same source as checkout).
3. Route every entitlement check through `EntitlementRules`; declare module, feature and ability gates per route group.
4. One navigation definition consumed by BQ-128.

## Scope

- **Acceptance criteria owned:** AC-PKT-01.
- **New requirements owned:** FR-SUB-014.
- **ADRs:** ADR-0021.
- **Absorbs:** BQ-033, BQ-115, BQ-119 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-SUB-01: entitlement only in `EntitlementRules`.

## Tests

- Gate tests per route group: hidden module 404, missing feature upgrade response, missing ability 403.
- Plan comparison and landing page list exactly `config/plans.php` (AC-PKT-01).
- Landing prices equal checkout prices.

## Docs to update

- `docs/domains/staged-modules.md`
- `docs/domains/subscription.md`
- `docs/domains/public-site.md`

## Evidence (fill when done)

Disetujui Tech Lead 2026-11-04 untuk sesi agen.

Implementation evidence (2026-11-04): new capabilities and former development-teaser routes default hidden per ADR-0021; no unbuilt module promoted. Config plans contains one capability/feature-text/team-limit map; unbuilt capabilities are omitted from both comparison lists. Prices and commercial numeric limits remain in the plans table (ADR-0021 decision 4), and landing prices now read that same source. `EntitlementRules` owns feature checks including unknown-feature denial for trials; overview/landing-page checks use it. `config/owner-navigation.php` declares fifteen menus once and `GetOwnerNavigation` filters module, plan and role for the sidebar and future shell consumers. Subscription CTA hidden from members without the ability. Pricing queries moved out of Blade; unsupported Google Calendar/down-payment marketing claims removed.

Tests: `ReleasePlanGatesTest` (5 tests, AC-PKT-01, eleven module/feature/role probe groups, role-filtered navigation, unknown capabilities and price source); original commercial-feature parity test retained. Existing report-behavior tests now explicitly enable their development module without changing their assertions. Navigation tests intentionally require hidden unbuilt routes rather than old locked teaser pages; ambiguous-multiple-membership pricing test now denies session tenant selection per ADR-0016.
Verification: full SQLite 695 passed / 3971 assertions; Pint fixed changed PHP; PHPStan no errors, three obsolete baseline entries removed; plan-name architecture allowlist emptied and three Blade-query allowances removed. No new locks or transactional writes; MySQL group not rerun for this read/config/gate item (last result BQ-131: 12/45).
