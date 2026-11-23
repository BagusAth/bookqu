---
id: BQ-171
title: Finish the audit polish items still open after the redesign
type: improvement
status: proposed
priority: P2
phase: 8
domain: booking
requirements: []
invariants: [INV-BOOK-01, INV-BOOK-02]
lane: heavy              # T5-15 and T5-16 touch booking state and logs; the rest is light-lane polish
approval: tech-lead
decision:
launch-blocker: false
depends-on: []
---

## Problem

The re-triage of [BQ-082](done/BQ-082-post-launch-audit-polish.md) on 22 Nov 2026 closed 69 of its 85 audit items as fixed, obsolete or not a defect. These 16 items are still present in the current code or cannot be confirmed from code alone. IDs keep their audit names (`BUG-Tn-NN`, details in the closed audit files `done/BQ-061` … `done/BQ-066`).

| Item | Area | Current evidence |
|---|---|---|
| T1-10 | Register | Login has the show/hide password toggle; `auth/register.blade.php` does not. |
| T1-14 | Landing | Anchor scroll snapping not verified in a browser. |
| T1-15 | Landing | Focus-visible ring on header links not verified. |
| T1-18 | Landing | Subtitle contrast not measured since the landing changes. |
| T1-19 | Landing | Theme transition flicker not verified. |
| T1-20 | Terms page | "Kembali ke Beranda" without an icon not verified. |
| T1-21 | Landing, storefront | `theme-color` exists on the auth pages only, not on `welcome` or the customer shell (could follow the business colour). |
| T2-17 | Services | Duration accepts any minute value; a 5- or 15-minute step is a product choice (Product Lead, `D-NNN` if wanted). |
| T3-16 | Checkout | When the booking session expires the customer returns to the start; whether a clear flash message shows is not verified. |
| T4-11 | Payment page | "Periksa status" on a 403/500 response: confirm the page shows an error state instead of failing silently. |
| T5-12 | Kelola (payment group) | Reviews are only offered on single bookings (`canReview` is false for a group). Deferred feature. |
| T5-15 | Walk-in | `CreateWalkInBooking` writes no `BookingLog` entry and sends no confirmation mail. |
| T5-16 | Booking status | `UpdateBookingStatus` changes one booking; sibling sessions of the same payment are not kept in step. |
| T5-21 | Kelola review form | Validation errors show only as the page-top note; the review form has no inline field errors. |
| T6-14 | Owner reviews | Redirect after replying or hiding a review: confirm page and filters are kept. |
| T6-19 | Subscription billing | Billing history rows have no invoice or pay-again action. |

## Plan

1. Verify the "not verified" rows in a browser first; close the ones that already hold.
2. Light lane: T1-10, T1-14, T1-15, T1-18, T1-19, T1-20, T1-21, T3-16, T4-11, T5-21, T6-14, T6-19, in small PRs per area.
3. Heavy lane, Tech Lead first: T5-15 (walk-in log and mail) and T5-16 (sibling sessions follow one status change, through `BookingState::canTransition`).
4. Product Lead: T2-17 and T5-12 only if wanted, as `D-NNN` entries.

## Invariants at risk

T5-16 changes booking states (INV-BOOK-01/02): every sibling transition goes through `BookingState::canTransition`. The other items are presentation only.

## Tests

Regression tests for T5-15, T5-16, T3-16 and T4-11; render assertions for the view items where meaningful.

## Docs to update

`docs/domains/booking.md` for T5-15 and T5-16.

## Evidence (fill when done)

_Fill in when done._
