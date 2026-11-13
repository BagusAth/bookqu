---
id: BQ-153
title: No-show booking state
type: feature
status: in-progress
priority: P1
phase: 4
domain: booking
requirements: [FR-BOOKING-020]
invariants: [INV-BOOK-01]
lane: heavy
approval: tech-lead
decision: D-013
launch-blocker: true
depends-on: [BQ-151, BQ-135]
---

# BQ-153: No-show booking state

Epic **E6 · Pro value and core rules** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

A customer who did not come can only be marked completed.

## Plan

1. Add `no_show` per ADR-0018; `MarkBookingNoShow` Action; panel action; labels; reports count it.

## Scope

- **Acceptance criteria owned:** none (infrastructure for other items).
- **New requirements owned:** FR-BOOKING-020.
- **ADRs:** ADR-0018.

## Invariants at risk

INV-BOOK-01/02.

## Tests

- Every case listed in ADR-0018 Consequences.

## Docs to update

- `docs/domains/booking.md`

## Evidence (fill when done)

Disetujui Tech Lead 2026-11-07 (backend lebih dulu, tiga bundle: A = BQ-133 + BQ-148, B = BQ-151 + BQ-152 + BQ-153, C = BQ-149 + BQ-150 + BQ-140). UI menyusul setelah BQ-129.

Backend 2026-11-13: `BookingState::STATUS_NO_SHOW` (paid → no_show, terminal), `bookings.status` enum, seat index and occupancy include `no_show`; `MarkBookingNoShow` (tenant lock, whole payment group, after `BookingRules::sessionHasEnded`, payment untouched) through `owner.bookings.status`; status lists for quota, schedule reports and analytics count it. Tests: `Booking/CapacityUnitsAndNoShowTest` (time condition, group, payment unchanged, terminal, Kasir allowed). Remaining: panel action, labels in owner lists, no-show rate in Laporan (BQ-147).
