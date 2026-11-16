---
id: BQ-137
title: Pelanggan: directory, quick filters and the customer panel
type: feature
status: done
priority: P2
phase: 4
domain: customers-reviews
requirements: []
invariants: [INV-TEN-01]
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: [BQ-129, BQ-131]
---

# BQ-137: Pelanggan: directory, quick filters and the customer panel

Epic **E2 · Core loop pages** of the owner portal redesign, planned in [BQ-126](../BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The customer page keeps identifiers in Alpine, interpolates search unescaped and has no phone view (BQ-100).

## Plan

1. Build `owner-portal.md` §8.5 with summary, quick filters, list and customer panel.
2. "Buat booking" prefills the walk-in form.
3. Carry every finding of BQ-100, including the escaping fixes.

## Scope

- **Acceptance criteria owned:** AC-PLG-01, AC-PLG-02, AC-PLG-03, AC-PLG-04.
- **New requirements owned:** none.
- **ADRs:** none.
- **Absorbs:** BQ-100 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-TEN-01 for customer identities and notes.

## Tests

- AC-PLG-01 to AC-PLG-04.
- Search input is escaped.

## Docs to update

- `docs/domains/customers-reviews.md`

## Evidence

Closed 2026-11-12. Merged into `integration` in PR #45 (merge commit `cd41f24`, 2026-11-10) after the Tech Lead audit. Before the merge: full suite 897 tests on SQLite, the item tests also on MySQL 8, Pint and PHPStan clean. Commit `ce276d1`. Tests: `Owner/PelangganPageTest` (AC-PLG-01 to AC-PLG-04, escaped search), `Owner/CustomerManagementTest`.
## Implementation notes

- **Page:** `Actions/Customer/ListCustomers` prepares the summary (total, new this month, returning = 2+ bookings), the quick filters and the paginated directory.
  - Quick filters: `new` = first booking this month; `regular` = 3 or more bookings; `dormant` = no booking for 60 days.
  - Phone cards below 768 px.
  - Rows open the shared customer panel through `@js` URLs.
- **One identity:** `Domain/Customer/CustomerIdentity` replaces four copies of the identity SQL (customer actions, owner search, dashboard). Behaviour is unchanged.
- **One spending rule:** `Actions/Customer/SumCustomerPayments` is used by the list and the panel.
  - The list used to count every `sukses` payment, including the payment of a cancelled booking. It now counts only paid, completed and no-show bookings, like the detail endpoint already did.
- **Panel:** `owner.customers.panel` (`GetCustomerPanel`) fills the BQ-129 customer panel. The panel has:
  - WhatsApp and email links;
  - figures: bookings, total paid, cancellations, no-shows;
  - the internal note, with one save at a time (`ownerCustomerNote` in `public/js/owner-ui.js`);
  - booking history, linking to the booking panel when BQ-135 adds it and to the filtered Booking list until then;
  - payment history.
- **Walk-in prefill:** "Buat booking" sends `walkin-prefill` to the shell walk-in form (AC-PLG-04). It shows only when the walk-in form exists.
- **Search:** the owner search now opens the customer panel by identity.
- **Tests changed by the spec:** `CustomerManagementTest`:
  - the empty-state copy is now the §8.5 sentence;
  - the summary test checks the customer count and per-customer paid amounts, because §8.5 has no revenue total in the summary.
- **BQ-100 findings carried:**
  - identifiers through `@js`;
  - phone cards;
  - note save guard;
  - the panel uses the shared accessible drawer.
