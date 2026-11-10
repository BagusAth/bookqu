---
id: BQ-143
title: Ulasan page with tabs and suggested replies
type: feature
status: in-progress
priority: P2
phase: 4
domain: customers-reviews
requirements: []
invariants: [INV-TEN-01]
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: [BQ-129]
---

# BQ-143: Ulasan page with tabs and suggested replies

Epic **E4 · Trust and acquisition pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Reviews load without a cap, interpolate search unescaped and use an ungoverned reply modal (BQ-101).

## Plan

1. Build `owner-portal.md` §8.11.
2. Carry every finding of BQ-101.

## Scope

- **Acceptance criteria owned:** AC-ULS-01, AC-ULS-02.
- **New requirements owned:** none.
- **ADRs:** none.
- **Absorbs:** BQ-101 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-TEN-01.

## Tests

- AC-ULS-01, AC-ULS-02.

## Docs to update

- `docs/domains/customers-reviews.md`

## Evidence (fill when done)

## Implementation notes

- **Wave:** built ahead of wave 4 under the ROADMAP rule. Its only dependency is BQ-129, and it touches no file of Developer A's open items (BQ-133, BQ-138, BQ-144).
- **Code:** `Actions/Review/GetReviewsPage` prepares the summary, the tabs with counts, search and the paginated list. The controller is thin. `ReplyToReview` and `ToggleReviewVisibility` hold the writes.
- **AC-ULS-02:** "Belum dibalas" is every review whose reply is null or empty, hidden reviews included.
- **AC-ULS-01:** the public booking page shows no reviews today, so a hidden review cannot appear there. The test guards this. Showing visible reviews publicly is in neither `owner-portal.md` nor `requirements.md`; it would be a product change (`D-NNN`) and is not built here.
- **Suggested replies:** three sentences per tone, chosen by rating (4–5 positive, 1–3 negative).
- **BQ-101 findings carried:**
  - pagination with 15 reviews per page and aggregates instead of loading every row;
  - search kept in a plain form field, with no Alpine interpolation;
  - a named route template through `@js`;
  - the shared accessible dialog with a single-submit guard.
- **Booking link:** each review links to the filtered Booking list until BQ-135 adds the booking panel.
