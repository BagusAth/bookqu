---
id: BQ-151
title: Booking seats and capacity per slot
type: feature
status: in-progress
priority: P1
phase: 4
domain: booking
requirements: [FR-BOOKING-021]
invariants: [INV-BOOK-03]
lane: heavy
approval: tech-lead
decision: D-014
launch-blocker: true
depends-on: [BQ-148]
---

# BQ-151: Booking seats and capacity per slot

Epic **E6 · Pro value and core rules** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

One booking per slot is enforced by a unique index that cannot allow capacity above 1.

## Plan

1. Create `booking_seats` with backfill and the seat index; remove `unique_active_booking_slot` in the same migration (ADR-0017 decision 2).
2. Capacity field in Layanan, participants in checkout and walk-in, remaining capacity in listings.

## Scope

- **Acceptance criteria owned:** AC-KAL-05.
- **New requirements owned:** FR-BOOKING-021.
- **ADRs:** ADR-0017.

## Invariants at risk

INV-BOOK-03/04/05.

## Tests

- MySQL: two claims for the last seat.
- AC-KAL-05.
- Backfill keeps every existing active booking.

## Docs to update

- `docs/domains/booking.md`
- `docs/domains/schedule.md`

## Evidence (fill when done)

Disetujui Tech Lead 2026-11-07 (backend lebih dulu, tiga bundle: A = BQ-133 + BQ-148, B = BQ-151 + BQ-152 + BQ-153, C = BQ-149 + BQ-150 + BQ-140). UI menyusul setelah BQ-129.

Backend 2026-11-13: `booking_seats` with backfill (seat 0 per existing booking) replaces `unique_active_booking_slot` in one migration; `BookingSeats` keeps seats in step through the Booking model; `services.kapasitas` (existing display values reset to 1, confirmed by the Tech Lead 2026-11-13) and `bookings.peserta`; `BookingRules::hasRoomFor`/`hasRoomSql` used by the claim, checkout validation and every listing (`seats_left`); participants in checkout, walk-in and reschedule; price per participant through `BookingPricing::quote`. Tests: `Booking/CapacityUnitsAndNoShowTest` (AC-KAL-05, capacity, seat index, per-participant charge), `Database/BookingSeatsMigrationTest` (backfill), `Actions/Booking/BookingSeatLockingTest` (MySQL, last seat). Remaining: capacity field in Layanan, participants in checkout/walk-in UI, remaining capacity shown in listings UI.
