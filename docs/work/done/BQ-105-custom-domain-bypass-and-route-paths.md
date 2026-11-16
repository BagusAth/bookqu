---
id: BQ-105
title: Hard-coded owner URLs and storefront links built outside CustomerBookingRoutes
type: improvement
status: rejected
priority: P2
phase: 8
domain: tenancy
requirements: [FR-TENANT-006, FR-TENANT-007, NFR-UX-002, NFR-MAINT-004]
invariants: [INV-TEN-02]
lane: light
approval: none
decision:
launch-blocker: false
depends-on: []
---

# BQ-105: Hard-coded owner URLs and storefront links built outside CustomerBookingRoutes

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-128](BQ-128-owner-app-shell-and-navigation.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-128.


Found during the owner UI deep audit on 2026-11-02. Corrected by the Tech Lead review on 2026-11-02: the quoted paths (`/owner/schedules/...`, `fetch('/owner/customers/note')`, a route named `tenant.show`) did not exist; the list below is re-checked against the code. Priority lowered to P2 and lane to light: it is a refactor, and no v1 tenant can set a custom domain yet (see D-005).

## Problem

### 1. Owner form actions and fetch URLs written by hand
These owner views build `/owner/...` URLs as strings instead of `route()` (or a route template passed with `@js`):

| View | Lines |
|---|---|
| `components/owner/modal-add-bulk-slots.blade.php` | 58 (`/owner/schedule/bulk-slots`) |
| `components/owner/modal-configure-availability.blade.php` | 46 (`/owner/schedule/availability`) |
| `components/owner/modal-default-pricing.blade.php` | 46 (`/owner/schedule/default-pricing`) |
| `components/owner/modal-complete-profile.blade.php` | 50 (`/owner/profile/complete`) |
| `components/owner/modal-reschedule-booking.blade.php` | 41 (available-slots fetch), 122 (reschedule action) |
| `components/owner/topbar.blade.php` | 479 (mark-as-read fetch) |
| `owner/partials/bookings/detail-modal.blade.php` | 175, 185, 194 (status actions) |
| `owner/partials/bookings/filters.blade.php` | 3 (search form) |
| `owner/partials/calendar/detail-modal.blade.php` | 250, 258, 295 (status actions), 320 (delete slot) |
| `owner/schedule.blade.php` | 209, 307 (delete slot) |
| `owner/reviews.blade.php` | 166 (reply) |
| `owner/categories.blade.php` | 357 (edit category, `url()` + concatenation) |

Tier 3 views (additional-items, vouchers, staff/resource modals, payment-settings) have the same pattern; fix them when those modules are released.

### 2. Storefront links built by hand
The "Booking Page" link and copy button in the topbar (`components/owner/topbar.blade.php:113-135`), the storefront link and copy button in settings (`owner/settings.blade.php:59`, `:84`) and the link on the subscription checkout (`owner/checkout.blade.php:120`) build `/{slug}` or `url('/'.$slug)` directly. The app already has `App\Support\CustomerBookingRoutes::url('customer.booking.program', …)`, which picks the custom-domain route when one is set. Today this only matters for tenants with a custom domain, and custom domains can be set only in the hidden landing-page module (D-005). The topbar part overlaps BQ-091 item 6.

## Plan

1. Replace each hand-written owner URL with `route()`; for Alpine-built URLs pass a route template via `@js` and fill the id.
2. Build storefront links and copy targets with `CustomerBookingRoutes::url(...)` in one place (the owner shell view model from BQ-090/BQ-091), and reuse it in settings and checkout.

## Invariants at risk

INV-TEN-02: storefront URLs must resolve to the same tenant through slug or custom domain.

## Tests

- View/feature: for a tenant with a custom domain, the topbar link and copy target use the custom-domain URL; without one, the slug URL.
- Guardrail test: no `action="/owner/`, `:action="'/owner/` or ``fetch(`/owner/`` remains in released owner views.

## Docs to update

`docs/domains/tenancy.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
