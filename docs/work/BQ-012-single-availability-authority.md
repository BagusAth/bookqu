---
id: BQ-012
title: Use one occupancy and availability definition everywhere
type: refactor
status: proposed
priority: P1
phase: 4
domain: schedule
requirements: [FR-SCHEDULE-009, FR-BOOKING-017, NFR-PERF-003]
invariants: [INV-BOOK-03, INV-SCH-01]
launch-blocker: true
needs-lead-approval: true
depends-on: [BQ-028]
---

# BQ-012: Use one occupancy and availability definition everywhere

## Problem

The occupancy rule is re-implemented in `BookingController::selectTime` (`:495`, hard-coded 15 minutes) and `BookingManageController::showReschedule` (`:302`, its own SQL that also ignores blocked dates — audit BUG-T5-08). `GetOwnerCalendarData.php:84` and `GenerateScheduleReport.php:57` treat every pending booking as occupied. Analytics and the schedule controller use their own "booked" status lists. Customers and owners can therefore see different availability.

## Plan

1. Decide the query API in an ADR (BQ-028).
2. Route every entry point through `BookingRules` / `AvailabilityRules` / `GetAvailableSchedules`.
3. Characterization tests first; then tests that calendar, report, storefront and both reschedule flows agree.

## Evidence

_Fill in when done._
