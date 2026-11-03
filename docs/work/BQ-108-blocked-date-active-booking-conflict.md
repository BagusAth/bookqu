---
id: BQ-108
title: Conflict detection and notification when blocking dates with existing bookings
type: bug-fix
status: approved
priority: P1
phase: 4
domain: schedule
requirements: [FR-SCHEDULE-004, FR-SCHEDULE-005, FR-NOTIFICATION-002, NFR-UX-005]
invariants: [INV-SCH-01]
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: []
---

# BQ-108: Conflict detection and notification when blocking dates with existing bookings

Found during deep audit of availability configuration and blocked dates on 2026-11-02.

## Problem

### 1. Silent Date Blocking Over Existing Customer Appointments
In `app/Actions/Schedule/UpdateScheduleAvailability.php:33-51` and `app/Http/Controllers/Owner/OwnerScheduleController.php:197-209`:
- When an owner configures availability and enters a date to block (`tanggal_block`), the action records an `OwnerBlockedDate` row for the tenant.
- **The system performs zero checks on existing bookings for that date.**
- If customers have already booked and paid for slots on that date, the date is immediately blocked from new bookings, but:
  - Existing confirmed bookings remain active on the calendar.
  - The owner receives no prompt or warning indicating that appointments already exist on that date.
  - Affected customers are not notified that the business has marked their scheduled date as closed/blocked.
  - No prompt is offered for rescheduling or handling the affected customer reservations.

### 2. Ambiguity in Storefront Availability
`INV-SCH-01` dictates that a schedule is bookable only if it is not on a blocked date. While future customers cannot book slots on that date, existing customers checking their booking status or attempting to reschedule encounter inconsistent operational state (their booking is confirmed, yet the storefront marks the studio as closed).

## Plan

1. In `UpdateScheduleAvailability` (or a pre-validation action), check for existing occupying bookings (`paid` or active `pending`) on the proposed `tanggal_block`.
2. If confirmed bookings exist:
   - Require explicit confirmation from the owner, returning a count and list of affected bookings.
   - Provide an operational review screen or warning toast prompting the owner to reschedule or contact affected customers before confirming the block.
3. If confirmed with active bookings, dispatch `BookingStatusChangedOwnerNotification` or an internal reminder task to resolve the appointments.

## Invariants at risk

`INV-SCH-01`: Schedule bookability must reflect operational availability without leaving existing confirmed bookings in limbo.

## Tests

- Feature test: blocking a date with 0 bookings succeeds immediately.
- Feature test: blocking a date with active paid bookings returns a warning error with the count of affected appointments.
- Feature test: unblocking a date (`DeleteBlockedDate`) restores schedule bookability correctly.

## Docs to update

`docs/domains/schedule.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
