---
id: BQ-133
title: Jam Buka & Harga: opening hours, price rules, closed dates and slots
type: feature
status: in-progress
priority: P1
phase: 4
domain: schedule
requirements: [FR-SCHEDULE-011, FR-SCHEDULE-012, FR-SCHEDULE-013, FR-SCHEDULE-014]
invariants: [INV-BOOK-03, INV-SCH-01]
lane: heavy
approval: tech-lead
decision: D-007
launch-blocker: true
depends-on: [BQ-129, BQ-107, BQ-108]
---

# BQ-133: Jam Buka & Harga: opening hours, price rules, closed dates and slots

Epic **E2 · Core loop pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The schedule page mixes bookings, a fake price panel and occupancy decided in Blade (BQ-096); there are no opening hours, peak-hour or special-date prices, and no bulk preview.

## Plan

1. Build `owner-portal.md` §8.8 with tabs Jam Buka, Harga, Libur & Tutup, Slot and the runway header.
2. Add opening hours, peak-hour rules and special-date prices to the one slot price rule; price simulator; "Terapkan harga dasar ke slot terbuka" replaces default pricing (YATIM-1).
3. Bulk creation with preview and cap; closed dates refuse while active bookings exist (BQ-108).
4. Remove the fake price panel and Recent Activity; carry every finding of BQ-096.

## Scope

- **Acceptance criteria owned:** AC-JBH-01, AC-JBH-02, AC-JBH-03, AC-JBH-04, AC-JBH-05.
- **New requirements owned:** FR-SCHEDULE-011, FR-SCHEDULE-012, FR-SCHEDULE-013, FR-SCHEDULE-014.
- **ADRs:** ADR-0005.
- **Absorbs:** BQ-096 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-BOOK-03 (occupancy only in `BookingRules`), INV-SCH-01.

## Tests

- AC-JBH-01 to AC-JBH-05.
- Price rule order and the simulator equal the price charged.

## Docs to update

- `docs/domains/schedule.md`

## Evidence (fill when done)

Disetujui Tech Lead 2026-11-07 (backend lebih dulu, tiga bundle: A = BQ-133 + BQ-148, B = BQ-151 + BQ-152 + BQ-153, C = BQ-149 + BQ-150 + BQ-140). UI menyusul setelah BQ-129.

Backend (bundle A, 2026-11-07): opening hours with breaks and `slot_open_days_ahead` (FR-SCHEDULE-011); peak-hour rules, special-date prices and the weekend rule priced by `SlotPricing` (FR-SCHEDULE-012); price simulator (AC-JBH-02); "Terapkan harga dasar" for future free slots (AC-JBH-04); bulk preview with new and existing counts, no duplicates (FR-SCHEDULE-014, AC-JBH-01); slot runway with the 7-day warning (AC-JBH-05). AC-JBH-03 was delivered by BQ-108. Bulk creation no longer stores the weekend price in `harga_override`. Tests: `SlotPricingTest`, `PriceRulesAndOpeningHoursTest`.

Order of rules (Tech Lead to confirm): slot override wins; otherwise a special-date price replaces base and weekend; otherwise the weekend rule (`multiplier` × base, `fixed` = price); then every matching peak rule adds its extra.

Tech Lead decision 2026-11-07: the bulk interval follows FR-SCHEDULE-014 (5–480 minutes, multiples of 5, defaulting to the service duration on the page) and may differ from the duration; `ServicesDomainEndToEndTest` was updated to the requirement. Remaining: the page tabs (needs BQ-129), removing the old default-pricing form, and moving occupancy out of the schedule Blade. Status stays in-progress.
