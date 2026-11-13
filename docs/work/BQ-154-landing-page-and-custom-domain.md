---
id: BQ-154
title: Landing page and custom domain for Pro
type: feature
status: approved
priority: P2
phase: 4
domain: public-site
requirements: [FR-PUBLIC-007]
invariants: [INV-TEN-01]
lane: heavy
approval: tech-lead
decision: D-005, D-009, D-017
launch-blocker: true
depends-on: [BQ-142, BQ-130]
---

# BQ-154: Landing page and custom domain for Pro

Epic **E6 · Pro value and core rules** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The landing builder and custom domain are hidden; there is no DNS check.

## Plan

1. Landing page and Domain sendiri sections of `owner-portal.md` §8.9.
2. DNS check job; custom-domain resolution stays as in ADR-0006.

## Scope

- **Acceptance criteria owned:** AC-HBK-03, AC-HBK-04.
- **New requirements owned:** FR-PUBLIC-007.
- **ADRs:** ADR-0006.

## Invariants at risk

INV-TEN-01: a domain resolves to exactly one tenant.

## Tests

- AC-HBK-03, AC-HBK-04.

## Docs to update

- `docs/domains/public-site.md`

## Approval (Tech Lead, 2026-11-13)

Approved, including the four decisions recorded on the `bagus` branch (schema `tenant_landing_pages` and the three `tenants.custom_domain_*` columns; DNS check by CNAME or A records as a queued job on save, on "Cek sekarang" and hourly; `TenantMiddleware` serves a domain only while it is `connected`, normalised and unique; the published landing page replaces the top of the storefront home).

Open points decided:

1. **TLS:** certificates for custom domains are issued by the server (for example on-demand certificates). This is a release prerequisite and is added to the ops checklist (BQ-017, BQ-022).
2. **Below Pro:** the hourly check also stops a connected domain when the business no longer has the Pro capability; it answers 404 like an unconnected domain. Required change before merge.
3. **Configuration:** `CUSTOM_DOMAIN_CNAME_TARGET` and `CUSTOM_DOMAIN_A_RECORDS` are set per environment by the Tech Lead during staging and production setup. Until then nothing connects (fail closed).

Before the PR: sync with `integration` (PR #46 changed `ExportScheduleReport`, `GenerateScheduleReport`, `OwnerAnalyticsController` and `routes/web.php`) and count `no_show` in Laporan (BQ-147, FR-BOOKING-020). The unused `landing-page` key of `config/modules.php` is removed in BQ-157.

## Evidence (fill when done)
