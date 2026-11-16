---
id: BQ-135
title: Booking page with task tabs and the shared booking panel actions
type: feature
status: in-progress
priority: P1
phase: 4
domain: booking
requirements: []
invariants: [INV-BOOK-01]
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: [BQ-129, BQ-131, BQ-087, BQ-121]
---

# BQ-135: Booking page with task tabs and the shared booking panel actions

Epic **E2 · Core loop pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The bookings page shows wrong amounts and payment states (BQ-095), the walk-in modal lacks totals (BQ-057), and actions are offered regardless of state and role.

## Plan

1. Build `owner-portal.md` §8.3 tabs, search, filters, rows and phone cards; export for Medium and up, formula-safe.
2. Fill the booking panel (§8.4): sections, history with actor, actions limited by `BookingState::canTransition`, role and open payment (BQ-087). Refund decisions, no-show and reschedule price difference come from BQ-138, BQ-153 and BQ-140.
3. Amounts from the payment record (rule 8).
4. Carry every finding of the absorbed items.

## Scope

- **Acceptance criteria owned:** AC-BKG-01, AC-BKG-02, AC-BKG-03, AC-BKG-04, AC-BKG-05, AC-PNL-01, AC-PNL-04.
- **New requirements owned:** none.
- **ADRs:** none.
- **Absorbs:** BQ-095, BQ-057 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-BOOK-01/02; INV-PAY-*: amounts from payments.

## Tests

- AC-BKG-01 to AC-BKG-05, AC-PNL-01, AC-PNL-04.

## Docs to update

- `docs/domains/booking.md`

## Evidence (fill when done)

2026-11-13: Booking now uses task tabs, search and filters, responsive rows, gated formula-safe CSV, and the BQ-129 shared panel also opened from Calendar. Payment amounts, actor history, and action inputs are prepared on the server. OwnerPortalWorkflowTest and the shared cancellation regression cover the new read/action contract. Remaining: no-show (BQ-153), reschedule price difference (BQ-140), unit/participant fields where the model does not yet supply them, and owner resend and notification entry points.
