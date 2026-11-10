---
id: BQ-136
title: Beranda: today, needs action and the new owner guide
type: feature
status: in-progress
priority: P1
phase: 4
domain: insights
requirements: [FR-DASH-008]
invariants: []
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: [BQ-129, BQ-131, BQ-089]
---

# BQ-136: Beranda: today, needs action and the new owner guide

Epic **E2 · Core loop pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The dashboard shows charts, truncated and misleading figures (BQ-094) and renders live data as HTML (BQ-089).

## Plan

1. Build `owner-portal.md` §8.1: one banner by priority, four figures, needs action, today and tomorrow, link card, quick actions, new owner guide.
2. Polling only while visible; text rendering only.
3. Move charts to Laporan (BQ-147); carry every finding of BQ-094.

## Scope

- **Acceptance criteria owned:** AC-BRD-01, AC-BRD-02, AC-BRD-03, AC-BRD-04, AC-BRD-05, AC-BRD-06, AC-BRD-07.
- **New requirements owned:** FR-DASH-008.
- **ADRs:** none.
- **Absorbs:** BQ-094 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

Figures from payments and bookings of the current tenant only.

## Tests

- AC-BRD-01 to AC-BRD-07.

## Docs to update

- `docs/domains/insights.md`

## Evidence (fill when done)

## Implementation notes

- **Schema (approved by the Tech Lead through the developer, 2026-11-09):** expand-only migration adding nullable `tenants.booking_link_shared_at` and `tenants.owner_guide_completed_at`. Guide step 4 and "never again" (AC-BRD-05) cannot be derived from other data.
- **Action:** `Actions/Dashboard/GetBeranda` replaces `GetOwnerDashboardOverview`, which is removed together with the old charts. Its three baseline entries are removed from `phpstan-baseline.neon`.
- **Polling:** `owner.dashboard.polling` now returns `figures`, `actions`, `today`, `tomorrow` and `next`. `POST owner.dashboard.link-shared` records the first share.
- **Revenue today:** successful booking payments with `created_at` today. Payments have no settlement timestamp; analytics uses the same column.
- **"Perlu tindakan" links:**
  - refund requests go to the Booking page until BQ-135 adds the decision panel;
  - Kasir cancellation requests go to `owner.bookings#permintaan-pembatalan` (anchor added);
  - ended paid bookings go to `owner.bookings?status=paid`;
  - reviews go to `owner.reviews`;
  - payout status changes count the unread `OwnerPayoutStatusChangedNotification` notifications.
- **Row actions:** "Tandai selesai" for paid bookings that have ended. "Tidak datang" waits for ADR-0018 (no control without a backend).
- **Not built:** participants per row (BQ-151) and the online/walk-in split of "Booking hari ini" (no column tells them apart).
- **Tests changed by the spec:**
  - `OwnerDashboardPollingTest` keeps its intents on the §8.1 contract. The "active services" figure test is dropped because §8.1 has no such figure.
  - `OwnerApplicationActionsTest` now covers `GetBeranda`.
  - `OwnerPortalFunctionalIntegrationTest` marks the guide done before checking revenue on Beranda.
  - `OwnerAppShellTest` now asserts that Chart.js loads on no owner page.
- **BQ-094 findings:** they concerned the removed charts and all-time cards. The remaining ones hold:
  - values are never truncated;
  - labels state their period ("hari ini");
  - there are no fake trend indicators;
  - status labels come from `StatusBadge`;
  - copy is Indonesian.
