---
id: BQ-163
title: Isolate scheduled jobs and bound booking workloads
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: booking
requirements: [FR-SCHEDULE-015, FR-NOTIFICATION-008, FR-BOOKING-024, FR-BOOKING-025, FR-TEAM-004, NFR-PERF-004, NFR-OBS-003]
invariants: [INV-TEN-01, INV-SCH-01, INV-BOOK-02, INV-BOOK-03, INV-SUB-01]
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: [BQ-162]
---

## Problem

An exception stops scheduled work for later tenants (T-11). Slot pickers issue a query per slot, and viewing or closing ended sessions loads every candidate ID (T-13). Booking log timestamps use the database clock's default representation while the slip assumes WIB (T-26).

## Plan

1. T-11: report and isolate each tenant failure across the three jobs. Keep automatic slot entitlement in `EntitlementRules`. Confirm the weekly summary eligibility for ended subscriptions.
2. T-13: use the existing occupancy expressions for slot lists. Replace materialized IDs in counts and iterate bulk completion candidates in bounded batches. Preserve eligibility checks and counts and coordinate the summary consumer with Developer B.
3. T-26: verify SQLite and MySQL timestamp semantics before selecting a consistent application write and WIB read strategy. Treat a historical backfill as a separate decision if the engines or environments stored different representations.

First-run completion policy for historic bookings belongs to D-027.

## Invariants at risk

Keep tenant scope on every query, preserve the only source of occupancy and state-transition rules, and apply entitlement at the existing boundary.

## Tests

Use focused cases for a scheduler that continues after tenant failure, bounded processing with unchanged eligibility, and WIB rendering of a known timestamp. Run related suites before one full pass.

## Docs to update

Update the relevant schedule, booking, notification, or operations descriptions only where approved behavior changes.

## Evidence

Proposed on 2026-11-18. Tech Lead decision pending.

T-26: local SQLite returned UTC for `CURRENT_TIMESTAMP`; the local MySQL session uses the system zone at UTC+07:00. A fixed offset for all historical logs would therefore give different results depending on where the log was written. The production session time zone has not been verified.
