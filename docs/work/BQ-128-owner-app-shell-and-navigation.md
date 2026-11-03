---
id: BQ-128
title: Owner app shell: sidebar, top bar, bottom navigation and global search
type: feature
status: proposed
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
