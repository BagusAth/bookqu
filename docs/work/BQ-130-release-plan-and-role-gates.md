---
id: BQ-130
title: Three server gates (module, plan, role) and one plan capability list
type: feature
status: proposed
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
