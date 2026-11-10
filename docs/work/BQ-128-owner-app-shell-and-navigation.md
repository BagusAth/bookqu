---
id: BQ-128
title: Owner app shell: sidebar, top bar, bottom navigation and global search
type: feature
status: in-progress
priority: P1
phase: 4
domain: staged-modules
requirements: [FR-SHELL-001, FR-SHELL-002]
invariants: [INV-TEN-01]
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: [BQ-130]
---

# BQ-128: Owner app shell: sidebar, top bar, bottom navigation and global search

Epic **E1 · Foundations** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The owner layout has a heavy sidebar and top bar, no phone navigation, no global search and no "+ Booking" action. Navigation, links and copy are built in several places (BQ-090, BQ-091, BQ-093, BQ-105).

## Plan

1. Build the shell of `owner-portal.md` §4: fifteen menus in five groups, top bar (bell, + Booking, search, account menu), bottom navigation below 768 px with the "Lainnya" sheet.
2. Render the menus from the single navigation definition of ADR-0021, filtered by module, plan and role on the server.
3. Search by booking code, customer name or phone; results open the booking or customer panel (BQ-129).
4. Indonesian labels and one vocabulary across the shell; storefront links only through `CustomerBookingRoutes`.
5. Carry every finding of the absorbed items into this work.

## Scope

- **Acceptance criteria owned:** none (infrastructure for other items).
- **New requirements owned:** FR-SHELL-001, FR-SHELL-002.
- **ADRs:** ADR-0021.
- **Absorbs:** BQ-090, BQ-091, BQ-093, BQ-105 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-TEN-01: search results come only from the current tenant.

## Tests

- `OwnerSidebarNavigationTest` per plan and per role (15 menus, hidden and locked cases).
- Search returns only own-tenant records; a foreign booking code returns nothing.
- Phone viewport shows the bottom bar.

## Docs to update

- `docs/domains/staged-modules.md`

## Evidence (fill when done)

## Implementation notes (2026-11-09, before merge)

- **One shell view model.** `Actions/Owner/GetOwnerShell` (composer on `layouts.owner-layout`) replaces the sidebar/topbar composers and the unused `adalahpro` composer; sidebar, top bar and phone navigation run no queries (BQ-090). Active state is decided by route name through `match` prefixes in `config/owner-navigation.php` (BQ-091).
- **Menus** come only from `config/owner-navigation.php` through the three gates; menus whose page does not exist yet (Unit, Halaman Booking, Promo, Bantuan, Pembayaran's new path) appear when their items add the route. Badges: bookings needing a decision (open refund requests, pending cancellation requests, for roles that decide them), slot runway warning (`GetSlotRunway`), unanswered reviews, trial days left.
- **Top bar:** search (`owner.search`, `SearchOwnerRecords`, tenant-scoped, throttled 60/min); results open the booking/customer panel once BQ-135/BQ-137 add `owner.bookings.panel` / `owner.customers.panel`, and until then the Booking or Pelanggan list filtered to the result. "+ Booking" opens the shared walk-in form with a date and the free slots of that date (`owner.bookings.walkin-slots`, `ListWalkInSlots`, single availability rule). Bell: one initialisation, list loaded on open, unread count refreshed every 2 minutes only while the tab is visible. Account menu: name, role, Pengaturan akun, Keluar.
- **Absorbed findings carried here:** no hover prefetch; Chart.js only on the dashboard, pinned to 4.4.4; Inter 400–700 only; no infinite animations or blur in the shell; no "Admin Portal"; no hand-written storefront or notification URLs in the shell; the 1024 px top bar no longer overflows; `TenantPermission::routeAbility` maps `owner.search` and `owner.bookings.walkin-slots`.
- **Not in this item:** page-level vocabulary and headers (each page item, with BQ-129 blocks); hand-written URLs inside page modals listed by BQ-105 (their page items); permanent redirects of merged paths (§10.2, with the page that replaces them); splitting the landing-page JS out of `app.js` (needs a Vite entry change).
- **Found while verifying:** `DatabaseSeeder` fails on current `integration` at `Staff::services()` (method removed); local seeding stops after tenants and services.
