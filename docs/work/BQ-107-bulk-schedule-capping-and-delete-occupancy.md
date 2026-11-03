---
id: BQ-107
title: Bulk schedule date range capping, past slot protection, and occupancy check on delete
type: bug-fix
status: approved
priority: P1
phase: 4
domain: schedule
requirements: [FR-SCHEDULE-002, FR-SCHEDULE-006, FR-SCHEDULE-010, NFR-PERF-002]
invariants: [INV-BOOK-03]
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: []
---

# BQ-107: Bulk schedule date range capping, past slot protection, and occupancy check on delete

Found during deep audit of schedule generation and slot deletion actions on 2026-11-02.

## Problem

### 1. Unbounded Date Range in Bulk Slot Creation (NFR-PERF-002)
In `app/Http/Requests/Schedule/BulkCreateScheduleRequest.php:36-37`:
```php
'tanggalmulai'   => ['nullable', 'date'],
'tanggalselesai' => ['nullable', 'date', 'after_or_equal:tanggalmulai'],
```
There is no upper ceiling on the date span between `tanggalmulai` and `tanggalselesai`. An owner can select a range covering multiple years (e.g. 3–5 years). In `app/Actions/Schedule/BulkCreateSchedules.php:94-139`, slot creation iterates through each day, evaluates conflicts, and executes individual `Schedule::create()` calls inside a single database transaction under `ClaimSlots::lockTenant`. For a 3-year span with 15-minute intervals across operational hours, this generates upwards of 50,000 queries in a single synchronous HTTP request, guaranteeing PHP execution timeouts and database lock contention.

### 2. Missing Past Date Protection (FR-SCHEDULE-010)
Neither `tanggal` nor `tanggalmulai` enforces `after_or_equal:today`. An owner can create bulk schedules in the past (e.g. year 2024), violating requirement FR-SCHEDULE-010 (Past Schedule Protection).

### 3. Slot Deletion Blocked by Expired Pending Bookings (INV-BOOK-03 Violation)
In `app/Domain/Schedule/ScheduleConflictRules.php:48-55`:
```php
public static function canDeleteSchedule(Schedule $schedule): bool
{
    return ! Booking::withoutGlobalScopes()
        ->where('idschedule', $schedule->id)
        ->where('status', '!=', 'cancelled')
        ->exists();
}
```
If a slot was claimed by a customer who abandoned payment after the 15-minute deadline, its booking record remains in the database with `status = 'pending'` until evicted lazily by a subsequent booking transaction. Because `'pending' != 'cancelled'`, `canDeleteSchedule` returns `false`, causing `DeleteSchedule.php` to abort with HTTP 403 (*"Slot memiliki booking aktif"*). The slot cannot be deleted even though it is unoccupied under the single occupancy rule (`BookingRules`, INV-BOOK-03).

## Plan

1. In `BulkCreateScheduleRequest.php`:
   - Add rule enforcing that `tanggalselesai` must not exceed 90 days from `tanggalmulai` (`before_or_equal:...` or custom validation rule).
   - Add `after_or_equal:today` to both `tanggal` and `tanggalmulai`.
2. Optimize bulk slot creation in `BulkCreateSchedules.php` to batch insert slots (`insert()`) per day after conflict checking rather than individual Eloquent `create()` calls.
3. Update `ScheduleConflictRules::canDeleteSchedule()` to rely on `BookingRules::isOccupied` or evaluate `BookingRules::applyOccupiesSlotCondition()`, allowing deletion when the only booking on the slot is an expired un-settled pending booking.

## Invariants at risk

`INV-BOOK-03`: Occupancy must be governed solely by `BookingRules` (pending hold deadline + settlement margin).

## Tests

- Feature test: submitting a bulk creation request with a date range exceeding 90 days fails validation with a clear error.
- Feature test: submitting bulk slot creation for past dates fails validation.
- Feature test: owner can delete a schedule slot whose only booking is an expired pending booking.
- Feature test: owner cannot delete a slot with a paid or active pending booking within its payment window.

## Docs to update

`docs/domains/schedule.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
