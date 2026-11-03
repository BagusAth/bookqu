---
id: BQ-091
title: One navigation model for the owner sidebar, breadcrumb and topbar
type: improvement
status: rejected
priority: P1
phase: 8
domain: staged-modules
requirements: [NFR-UX-001, NFR-UX-002]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: [BQ-092, BQ-093]
---

# BQ-091: One navigation model for the owner sidebar, breadcrumb and topbar

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-128](../BQ-128-owner-app-shell-and-navigation.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-128.


Found in the owner UI audit of 2026-11-02.

## Problem

Navigation is defined three times (sidebar array, breadcrumb `str_contains` chain, page `<h1>`), so they disagree, and the shell repeats the same destinations:

**Bugs**
- On `/owner/schedule-report` both "Schedule" and "Schedule Report" are active, because `owner/schedule-report` starts with `owner/schedule` (`resources/views/components/owner/sidebar.blade.php:14-32`); the BOOKING section is treated as the active section, so ANALYTICS may stay collapsed.
- `/owner/settings/balance` (and every checkout page) shows the breadcrumb "Dashboard" because the breadcrumb chain has no entry for it (`topbar.blade.php:8-72`).
- At 1024 px the topbar overflows and the whole page scrolls horizontally (measured `scrollWidth` 1028 > 1009); the "Booking Page" button wraps to two lines and the owner name truncates.
- The plan card shows the wrong state: trial vs paid is read from the `status` column, "days left" divides by 14 for every plan (trial 7, paid 30) with a minimum bar width of 8 %, and the fallback says "Free" although no free plan exists (`sidebar.blade.php:7-10`, `:513-528`). Expired trials still show Pro (BQ-086).
- Locked "Integrations" has no lock badge while the two locked analytics items do (`sidebar.blade.php:146-151`).
- The brand block says "Admin Portal" in the owner app (`sidebar.blade.php:231`).
- The "Booking Page" link and the copy button build `/{slug}` by hand (and inside a quoted JS string), ignoring custom domains and `CustomerBookingRoutes` (`topbar.blade.php:113-135`); the copy shows a success toast without handling a clipboard failure.

**Redundancy**
- Notifications: sidebar item and topbar bell.
- Logout: sidebar button and profile menu.
- Subscription: sidebar plan card, sidebar item and profile menu.
- Business settings: sidebar item, topbar "business status" chip (with a permanent green "online" ping that means nothing) and profile menu.
- Seven collapsible sections for 14 items; two sections hold a single item (CUSTOMERS → Customers, MARKETING → Reviews); the active section cannot be collapsed and collapsed state is stored in `localStorage`.
- Breadcrumb repeats the page title ("Services / Services") and section names differ from the sidebar ("Services" vs "BUSINESS", "Account" for Notifications).

## Plan

1. One owner navigation definition (config or a small class) with route names, labels (BQ-093), icon, module key (`config/modules.php`) and group. Sidebar, breadcrumb and page titles read it; active state is decided by route name only.
2. Flatten the sidebar into four short groups without accordions, for example: Ringkasan (Dashboard), Operasional (Kalender, Jadwal, Booking), Bisnis (Layanan, Kategori, Pelanggan, Ulasan), Pengaturan (Bisnis, Saldo, Langganan); locked Tier 2 items in one group with the same lock marker. Exact grouping is a design call in this item; Tier 2/3 visibility does not change (ADR-0002, ADR-0011).
3. Each destination appears once: bell in the topbar (no sidebar item), logout and account links in the profile menu, plan summary only in the sidebar card. Remove the business status chip.
4. Breadcrumb: group / page from the model; hide it when it only repeats the title.
5. Plan card from `EntitlementRules` (BQ-086): plan name, real period end, days left against the real period, renewal link.
6. Topbar fits at 1024 px and on phones; "Booking Page" and copy use `CustomerBookingRoutes` and `@js`.
7. `aria-current="page"` on the active link; Indonesian `aria-label`s.
8. Update `OwnerSidebarNavigationTest` together with the intended label and group changes (the test asserts "Admin Portal" and the section names); keep its Tier 2/3 assertions unchanged.

## Invariants at risk

Release-tier visibility (ADR-0002/0011): hidden modules stay out of navigation, locked ones keep their lock.

## Tests

- `OwnerSidebarNavigationTest` (updated labels) plus: exactly one active item on every owner route (data provider over all owner GET routes, including schedule-report and settings/balance); breadcrumb present for every owner route.
- Manual: 375 px, 768 px, 1024 px, 1440 px — no horizontal page scroll.

## Docs to update

`docs/domains/staged-modules.md` (sidebar uses the module registry; closes the BQ-033 sidebar gap if done here).

## Evidence (fill when done)

_Fill in when done._
