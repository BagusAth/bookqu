---
id: BQ-105
title: Storefront links bypass tenant custom domain and hardcoded owner URL paths
type: bug-fix
status: proposed
priority: P1
phase: 8
domain: tenancy
requirements: [FR-TENANT-006, FR-TENANT-007, FR-PUBLIC-001, NFR-UX-002]
invariants: [INV-TEN-02]
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: []
---

# BQ-105: Storefront links bypass tenant custom domain and hardcoded owner URL paths

Found during the owner UI deep audit on 2026-11-02 across owner shell components, settings, and modals.

## Problem

### 1. Storefront Links and Copy-Link Bypassing Custom Domains (FR-TENANT-007)
Across several owner components, links to the tenant's public booking storefront and the "Salin Link Toko" action hardcode the platform root URL with slug:
- `resources/views/components/owner/topbar.blade.php:114, 131`:
  `href="/{{ $tenant->slug }}"`
  `@click="navigator.clipboard.writeText(window.location.origin + '/{{ $tenant->slug }}'); ..."`
- `resources/views/owner/settings.blade.php:59, 84`:
  `href="/{{ $tenant->slug }}"`
  `@click="navigator.clipboard.writeText(window.location.origin + '/{{ $tenant->slug }}')"`
- `resources/views/owner/checkout.blade.php:120`:
  `href="/{{ $tenant->slug }}"`

When a tenant configures and verifies a custom domain (`tenants.custom_domain`), these hardcoded paths completely bypass the custom domain. Owners previewing their business or copying the public link to share on social media copy the platform path (`bookqu.test/slug` or `bookqu.id/slug`) instead of their branded custom domain (e.g. `booking.bramastudio.com`), undermining the custom domain feature.

### 2. Hardcoded `/owner/...` URI Strings
Multiple owner modals and asynchronous actions bypass Laravel named routes in favor of brittle hardcoded path strings:
- `resources/views/components/owner/modal-add-bulk-slots.blade.php:13`: `action="/owner/schedules/bulk"`
- `resources/views/components/owner/modal-configure-availability.blade.php:14`: `action="/owner/schedules/configure-availability"`
- `resources/views/components/owner/modal-default-pricing.blade.php:13`: `action="/owner/schedules/default-pricing"`
- `resources/views/owner/reviews.blade.php:231`: `:action="'/owner/reviews/' + activeReview.id + '/reply'"`
- `resources/views/owner/partials/customers/detail-modal.blade.php:108`: `fetch('/owner/customers/note', ...)`
- `resources/views/owner/partials/calendar/detail-modal.blade.php:255`: `fetch('/owner/bookings/' + bookingId + '/status', ...)`

Hardcoding path strings breaks when route structures evolve, prevents automated route URL verification in tests, and risks desynchronization between routes and views.

## Plan

1. Introduce or reuse a dedicated helper/accessor on `Tenant` (or `CustomerBookingRoutes`) that outputs the canonical public storefront URL:
   - If `custom_domain` is set and active: `https://{custom_domain}`
   - Otherwise: `route('tenant.show', ['slug' => $tenant->slug])`
2. Update `topbar.blade.php`, `settings.blade.php`, and `checkout.blade.php` to use the canonical URL helper for both preview links and clipboard copying.
3. Replace all hardcoded `/owner/...` string paths with standard `route(...)` named route generators.

## Invariants at risk

`INV-TEN-02`: Tenant context resolution must remain faithful to custom domain and slug binding.

## Tests

- Feature test: for a tenant with `custom_domain = 'booking.example.com'`, the topbar storefront link and clipboard copy target resolve to `https://booking.example.com`.
- Feature test: for a tenant without custom domain, it resolves cleanly to `route('tenant.show', $tenant->slug)`.
- Architecture / view test: no raw `/owner/...` form actions or fetch endpoints remain in owner Blade views.

## Docs to update

`docs/domains/tenancy.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
