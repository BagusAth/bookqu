---
id: BQ-129
title: Owner shared building blocks: page header, tabs, panels, walk-in form, dialogs
type: feature
status: proposed
priority: P1
phase: 8
domain: insights
requirements: []
invariants: []
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: []
---

# BQ-129: Owner shared building blocks: page header, tabs, panels, walk-in form, dialogs

Epic **E1 · Foundations** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Owner pages each build their own modals, tabs, badges and empty states; booking details exist in three different modals; dialogs lack focus handling (BQ-092, BQ-106).

## Plan

1. Build the blocks of `owner-portal.md` §9 as Blade components: page header, tabs with URL state, booking panel frame, customer panel frame, walk-in form, status badges, upgrade prompt, gallery picker frame, empty state, confirm dialog, toast, permission-denied page.
2. One booking panel component opened from every entry point (AC-PNL-06); page items fill its sections and actions.
3. Dialogs and drawers: `role="dialog"`, focus trap, Escape to close, no native `alert`/`confirm`.
4. Carry every finding of the absorbed items into this work.

## Scope

- **Acceptance criteria owned:** AC-PNL-06.
- **New requirements owned:** none.
- **ADRs:** none.
- **Absorbs:** BQ-092, BQ-106 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

None directly; components are presentation only (`.ai/rules/frontend.md`).

## Tests

- Component rendering tests for each block.
- AC-PNL-06: the same panel opens from Beranda, Kalender, Booking, Pelanggan, search and notifications.

## Docs to update

None beyond this item.

## Evidence (fill when done)
