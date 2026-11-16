---
id: BQ-097
title: Remove non-functional controls and hidden-module remnants from released owner pages
type: bug-fix
status: rejected
priority: P1
phase: 8
domain: staged-modules
requirements: [NFR-UX-005, FR-NOTIFICATION-001]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: []
---

# BQ-097: Remove non-functional controls and hidden-module remnants from released owner pages

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-157](BQ-157-remove-withdrawn-modules.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-157.


Found in the owner UI audit of 2026-11-02. A control that looks like a setting but does nothing misleads the owner; hidden Tier 3 modules must not leak into released pages (ADR-0002, ADR-0011).

## Problem

**Controls that do nothing**
- Settings → "Notification Preferences": Email, SMS, New Booking and Payment toggles are stored only in `localStorage` ("Tersimpan di browser") and are never read by the server; SMS does not exist (`resources/views/owner/settings.blade.php:170-221`).
- Schedule → "Bulk Price Adjustments" with fake surge and early-bird promotions and a dead settings button (detail in BQ-096; remove here if BQ-096 is not started).
- Topbar "business status" chip with a permanent green "online" ping (BQ-091).
- Settings sections numbered 1, 2, 3, 6 in code comments; check that nothing references the removed 4 and 5.

**Hidden modules visible in released pages** (staff/resources and add-ons are `hidden` in `config/modules.php`)
- Services cards list "Staff: …", "Resource: …" and "+N Add-ons" (`resources/views/owner/programs.blade.php:217-238`).
- Add/edit service modals render staff, resource and add-on pickers when legacy data exists (`components/owner/modal-add-program.blade.php:215-260`, `modal-edit-program.blade.php:232-…`).
- Bookings table has a Staff/Resource column; the detail modal shows "General Staff" / "General Facility" (`partials/bookings/desktop-table.blade.php:91-96`, `detail-modal.blade.php:120-124`).
- Sidebar icon switch still carries icons for hidden modules (harmless, remove with BQ-090's icon component).

Added 2026-11-02 (deep audit):
- Integrations page links to a Tier 3 hidden route (`app/Http/Controllers/Owner/OwnerIntegrationController.php:33` provides `"action_url" => route('owner.settings.payment-setting')` labeled "Konfigurasi Midtrans →"). Route `owner.settings.payment-setting` is wrapped by `middleware('module:direct-payment')`, which returns an immediate 404 because `direct-payment` is `hidden` in `config/modules.php`. It also violates ADR-0001 and BQ-079 (platform payment only in v1).
- Subscription page leaks hidden staff module (`resources/views/owner/subscription.blade.php:67-73` renders a "Staff Members" usage quota bar displaying "0 / 5 Staf Aktif"), even though staff is a Tier 3 hidden module.

## Plan

1. Remove the notification preference toggles. If the Product Lead wants owner-controlled notifications, that is a new entry in `docs/product-decisions.md`.
2. Remove the fake pricing panel and the status chip (or confirm BQ-096/BQ-091 did).
3. Gate every staff/resource/add-on element in released views on the module state (`config/modules.php` via the same helper the routes use), so they appear only when the module is released.
4. Keep `OwnerSidebarNavigationTest` and the module tests green.

## Invariants at risk

Release tiers (ADR-0002/0011): hidden modules stay invisible; their data stays untouched.

## Tests

- Feature: with staff/resources/add-ons `hidden`, services index, service modals, bookings index and detail payload contain no staff/resource/add-on UI even when the tenant has such data.
- Feature: settings page has no notification preference toggles.

## Docs to update

`docs/domains/staged-modules.md` known gaps.

## Evidence (fill when done)

_Fill in when done._
