---
id: BQ-035
title: Clean up duplicate routes and main-domain detection
type: refactor
status: done
priority: P2
phase: 6
domain: tenancy
requirements: [FR-PUBLIC-001, FR-PUBLIC-007]
invariants: [INV-TEN-01, INV-TEN-02]
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-035: Clean up duplicate routes and main-domain detection

## Problem

Duplicate routes: `owner.programs*` vs `owner.services*`, `owner.settings.payment-setting` vs `owner.settings.payments`, `owner.settings` vs `owner.settings.business`. The main domain is defined twice (`app/Http/Middleware/TenantMiddleware.php:22` and the exclusion list in `routes/web.php`).

## Plan

Keep one route per action (with redirects where links exist) and one source for the main-domain list (config); tests for the storefront on both custom domain and slug.

## Evidence

- `94271f0` keeps `owner.services`, `owner.payments` and `owner.settings` as the canonical names. Existing GET bookmarks for programs, payment settings and business settings return 301 and preserve their query strings. The redirects keep their previous authorization checks. The old program write routes were already retired by ADR-0024.
- Server-side callers, Blade links and tests use the canonical names or URLs. `php artisan route:list --path=owner/settings` confirmed a single settings page action and redirect-only bookmark routes.
- `custom-domains.platform_hosts` and the configured application host feed one host rule. It drives route matching, tenant lookup, storefront URL generation and custom-domain validation. Tests cover slug routing on platform hosts, connected and unconnected custom domains, and domains that share a string prefix with a platform host.
- Related tests passed (96 tests, 1,223 assertions). The full SQLite suite passed (987 tests, 6,536 assertions). Pint and PHPStan passed on PHP 8.4.25, with CLI OPcache disabled for PHPStan.
