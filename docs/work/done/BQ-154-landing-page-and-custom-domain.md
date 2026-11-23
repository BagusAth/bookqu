---
id: BQ-154
title: Landing page and custom domain for Pro
type: feature
status: done
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

Epic **E6 · Pro value and core rules** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The landing builder and custom domain are hidden; there is no DNS check.

## Plan

1. Landing page and Domain sendiri sections of `owner-portal.md` §8.9.
2. DNS check job; custom-domain resolution stays as in ADR-0006.

## Scope

- **Acceptance criteria owned:** AC-HBK-03, AC-HBK-04.
- **New requirements owned:** FR-PUBLIC-007.
- **ADRs:** ADR-0006.

## Decisions (Tech Lead, recorded through the developer, 2026-11-12)

1. **Schema:** a new table `tenant_landing_pages` (one row per business: template, sections, headline, banner from the media gallery, `published_at`) and three `tenants` columns for the domain check: `custom_domain_status` (`pending`, `connected`, `not_connected`), `custom_domain_checked_at`, `custom_domain_verified_at`.
2. **DNS:** a domain points by CNAME to a host, or by A records to IP addresses, both set per environment in configuration (`CUSTOM_DOMAIN_CNAME_TARGET`, `CUSTOM_DOMAIN_A_RECORDS`). The check runs as a queued job when the domain is saved, from a "Cek sekarang" button, and hourly for every domain.
3. **Resolution:** `TenantMiddleware` resolves a custom domain only while its last check is `connected`. A saved, unchecked or failing domain answers 404. Domains are stored normalised (lower case, no scheme, path or trailing dot) and are unique across businesses.
4. **Where the landing page shows:** once published, the template, headline, sections and banner replace the top of the storefront home (`/{slug}` and the custom domain root), followed by the existing booking flow.

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

2026-11-16 (Tech Lead, Developer B unavailable): branch `agathan-bagus-sync` takes Developer B's commits, merges `integration` (conflicts: the schedule report actions removed in favour of Laporan, `OwnerAnalyticsController`, `routes/web.php`, `phpstan-baseline.neon`, this file), and adds the Tech Lead decisions: the ownership TXT record (`_bookqu.<domain>` = `bookqu-verify=<token>`, migration `add_custom_domain_token_to_tenants_table`), the stop below Pro in every check, the release step in `operations.md` §8, and `BookingState::STATUS_NO_SHOW` in Laporan. Tests: `Owner/LandingPageAndDomainTest` (TXT required, another business's token refused, new domain new token, hourly stop below Pro), `Customer/CustomDomainBookingTest`, `Customer/CustomerAccessTest`.

## Implementation notes

- **Wave:** built ahead under the ROADMAP rule. Its dependencies BQ-142 and BQ-130 are done, and it touches no file of Developer A's open items.
- **Sections:** Landing page and Domain sendiri are the last two sections of Halaman Booking (`GetBookingPageData`). Below Pro each shows the upgrade prompt; the write and preview routes carry `subscription:landing-page` (AC-HBK-03), and `booking-page.manage` keeps Kasir out.
- **Landing page:** `tenant_landing_pages`, `SaveLandingPage` (save, publish, hide), `GetLandingPage` (published for the storefront, draft for the preview), partial `customer/booking/partials/landing`. Templates and sections are fixed lists in `TenantLandingPage`; the banner must be an image of the business's own gallery, and deleting it from the gallery asks first and clears it.
- **Domain:** `CustomDomain` (normalise, allow, points-to-platform), `DnsLookup` (live DNS, swapped in tests), `UpdateCustomDomain`, `CheckCustomDomain`, `CheckCustomDomainJob`, command `domains:check` (hourly). `TenantMiddleware` resolves a host only while `custom_domain_status = connected`. With no DNS target configured, nothing connects and the page says so.
- **Removed:** `OwnerLandingPageController`, `owner/landing-page.blade.php` and its `blade-queries` allowlist entry; `/owner/landing-page` answers 301 to the Landing page section. The `landing-page` key in `config/modules.php` is no longer used by any route; left for the Tech Lead.
- **Not decided here (Tech Lead):**
  - TLS certificates for custom domains must be issued by the server (for example on-demand certificates) before release;
  - a business that drops below Pro keeps a connected domain resolving; whether the hourly check should also stop it is open;
  - set `CUSTOM_DOMAIN_CNAME_TARGET` and `CUSTOM_DOMAIN_A_RECORDS` per environment.
- **Tests moved to the new contract (same intent):** `CustomDomainBookingTest` and `CustomerAccessTest` mark their domain connected (plus a new case: a pending or failing domain answers 404); `AnalyticsAndSubscriptionTest`, `OwnerPortalFunctionalIntegrationTest` and `SubscriptionRulesAndMechanismsTest` use the new Pro routes; `ReleaseGateAndHardeningTest` maps the old path to `booking-page`.

Closed 2026-11-16 (Tech Lead): merged in PR #51 (`7c90492`), including the TXT ownership proof and the stop below Pro. Before release, the ops steps in `operations.md` §8 apply: DNS targets per environment, TLS certificates for custom domains, and `php artisan domains:check` right after the migration.
