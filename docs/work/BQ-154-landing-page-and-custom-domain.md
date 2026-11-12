---
id: BQ-154
title: Landing page and custom domain for Pro
type: feature
status: in-progress
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

## Evidence (fill when done)

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
