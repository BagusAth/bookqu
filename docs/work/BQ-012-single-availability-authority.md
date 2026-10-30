---
id: BQ-012
title: Use one occupancy and availability definition everywhere
type: refactor
status: approved
priority: P1
phase: 4
domain: schedule
requirements: [FR-SCHEDULE-005, FR-SCHEDULE-009, FR-BOOKING-017, FR-BOOKING-018, FR-BOOKING-019, NFR-PERF-003, NFR-PERF-004]
invariants: [INV-TEN-01, INV-BOOK-03, INV-BOOK-04, INV-BOOK-05, INV-BOOK-06, INV-SCH-01]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-028, BQ-023, BQ-067]
---

# BQ-012: Use one occupancy and availability definition everywhere

ADR-0009 (2026-10-30) replaces the 15-minute-from-creation hold with the payment deadline + settlement margin; implement the hold through `BookingRules` with BQ-071.

Design and plan approved by agathan in [ADR-0005](../adr/ADR-0005-slot-bookability-module.md), 2026-10-30. Implementation remains Phase 4, subject to the roadmap gates; this item is not implemented or verified yet.

## Problem

The occupancy rule is re-implemented in `BookingController::selectTime` (`:495`, hard-coded 15 minutes) and `BookingManageController::showReschedule` (`:302`, its own SQL that also ignores blocked dates — audit BUG-T5-08). `GetOwnerCalendarData.php:84` and `GenerateScheduleReport.php:57` treat every pending booking as occupied. Analytics and the schedule controller use their own "booked" status lists. Customers and owners can therefore see different availability.

## Plan

1. Characterize existing callers and rejection/cache behavior; use BQ-023's MySQL suite for locking evidence and BQ-067's Payment lifecycle for group expiry.
2. Build ADR-0005's module with batch data acquisition and pure evaluation, shared advisory reads and a locked claim inside the booking/reschedule Action's outer transaction. Keep quota, pricing and payment transitions with their existing owners.
3. Apply the tenant-first protocol to every relevant writer listed in ADR-0005, including payment/subscription jobs, schedule/date mutations and service/fulfillment CRUD/pivots. Reload eligibility and source-booking state after synchronization; validate all ownership before cleanup.
4. Replace direct stale-pending cancellation with whole-group expiry requests to Payment lifecycle. Refuse anomalies; roll back all operation mutations on rejection.
5. Move storefront, calendar, report, walk-in and both reschedule paths to the shared authority. Preserve multi-slot atomicity and the existing 15-minute grace.
6. Move availability cache key construction and invalidation into the module. Cover actual ranges, dates and reschedule exclusions; recompute time/grace on reads and invalidate all affected variants after commit.
7. Test the contract through the module interface: read/write agreement, tenant isolation before cleanup, rollback, blocked dates, service/fulfillment eligibility, time/grace boundaries, grouping/anomalies and cache behavior. Prove competing claims, closure/service races and payment/expiry/reschedule races on MySQL; run affected tests, then the full suite.
8. Add BQ-025 architecture checks as callers migrate. Update the schedule, booking and affected payment/subscription domain descriptions only when implementation changes; record blocked-date acceptance in BQ-069.

## Decision and scope

Also from BQ-071: add a MySQL test of the expiry sweep against a claim on the same slot (sweep reclaims through the Payment lifecycle; the claim locks the tenant first).

ADR-0005 is the authority for the module contract, synchronization and closure policy. BQ-069 follows this module for blocked-date acceptance. Management-link authorization and the pending-payment deadline decision remain separate Phase 2 candidates.

## Evidence

_Fill in when done._
