---
id: BQ-069
title: Blocked dates are not enforced when creating or rescheduling a booking
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: schedule
requirements: [FR-SCHEDULE-005, FR-BOOKING-017]
invariants: [INV-SCH-01]
launch-blocker: true
needs-lead-approval: true
depends-on: [BQ-012]
---

# BQ-069: Blocked dates are not enforced when creating or rescheduling a booking

## Problem

Blocking a date only inserts an `owner_blocked_dates` row (`UpdateScheduleAvailability:33-37`); schedules on that date stay `tersedia`. Listings hide them, but `CreateBooking` and `RescheduleBooking` never check blocked dates, so a direct request (or a stale page) can book or move a booking onto a closed day. No customer or reschedule test covers blocked dates. Related: audit BUG-T5-08 (customer reschedule ignores blocked dates). Found by the 2026-10-30 architecture review.

## Plan

Resolve through the single availability authority (BQ-012, review candidate #2 "Slot bookability module"): the locked claim refuses blocked dates for every purpose (new booking, reschedule, walk-in). Until then, a minimal guard in both actions with regression tests is acceptable if launch comes first.

## Evidence

_Fill in when done._
