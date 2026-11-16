---
id: BQ-129
title: Owner shared building blocks: page header, tabs, panels, walk-in form, dialogs
type: feature
status: done
priority: P1
phase: 4
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

Epic **E1 · Foundations** of the owner portal redesign, planned in [BQ-126](../BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

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

## Evidence

Closed 2026-11-12. Merged into `integration` in PR #45 (merge commit `cd41f24`, 2026-11-10) after the Tech Lead audit. Before the merge: full suite 897 tests on SQLite, the item tests also on MySQL 8, Pint and PHPStan clean. Commit `2d3657d`. Tests: `Owner/SharedBuildingBlocksTest`, `Domain/Booking/BookingStateTest` (labels); guardrail `native-dialogs`.
## Implementation notes (2026-11-09, before merge)

- Blocks live in `resources/views/components/owner/` (index in its `README.md`); behaviour in `public/js/owner-ui.js`, loaded by the owner layout before Alpine starts.
- **Split with the page items.** BQ-129 delivers the booking and customer panel frames: one instance each in the owner layout, opened by `open-booking-panel` / `open-customer-panel` with a JSON URL, and the JSON contract documented in each component. The endpoints and the actions are filled by BQ-135 (booking) and BQ-137 (customer). AC-PNL-06 is complete when Beranda, Kalender, Booking, Pelanggan, search (BQ-128) and notifications (BQ-145) open the panel through that event.
- **Labels.** `BookingState::formatLabel` now returns the labels of `owner-portal.md` §8 (it had no callers outside its unit test). Refund labels live in `App\Support\StatusBadge` until the refund domain has a formatter.
- **Walk-in form.** It posts only the fields `owner.bookings.walkin` accepts today; participants and add-ons appear with BQ-151 and BQ-150 (§3 rule 6).
- **Absorbed findings carried here:** one token palette from the BQ-126 prototype (`resources/css/app.css`), accessible dialogs and drawers (BQ-106), one flash mechanism (layout `pesan` duplicate removed), confirm dialog with a tone instead of a fixed red trash can, toast ids, `aria-live` and close label, native `confirm()`/`alert()` removed with guardrail `native-dialogs`. Restraint rules are proposed in the components README for the Tech Lead to adopt into `.ai/rules/frontend.md`. Migrating each page's own badges, headers, inline flashes and hard-coded colours happens in its page item (wave 3 onwards).
