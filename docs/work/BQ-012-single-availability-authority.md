---
id: BQ-012
title: Use one occupancy and availability definition everywhere
type: refactor
status: in-progress
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

Built on branch `bq-012-slot-bookability` (2026-10-30).

**One rule.**
- `AvailabilityRules::refusal()` returns a `SlotRefusal`. It checks, in order: ownership and requested date, slot status, closed date, past time, service and fulfillment eligibility, occupancy.
- `getSlotAvailabilityStatus()` (listings) is derived from it, so readers and the claim cannot disagree.

**Locked claim: `App\Actions\Schedule\ClaimSlots`.** It joins the operation's transaction and:
- locks the tenant row first, then the schedules;
- verifies every schedule's tenant and service before any cleanup;
- reclaims stale pending bookings through the Payment lifecycle (whole group);
- re-checks each slot with `refusal()`.

A refusal throws `SlotClaimRefused`, so stale cleanup rolls back too; before, the early `return` inside the transaction committed it. A stale pending booking on a `sukses` payment is now refused and logged instead of cancelled (decision 6).

**Callers moved:**
- customer checkout (`CreateBooking`);
- owner walk-in (`CreateWalkInBooking`): now also refuses past slots, closed dates and inactive services, as requirements "Walk-In Booking Rules" demand;
- owner and customer reschedule (`RescheduleBooking`): reloads the source booking after the locks.

**Readers moved to the occupancy rule:**
- owner calendar "available" filter (`GetOwnerCalendarData`);
- `GenerateScheduleReport`, `ExportScheduleReport`;
- customer reschedule page, which no longer offers closed dates (BUG-T5-08).

**Tenant-first writers (decision 4, closure decision 7):** `CancelBooking`, `DeleteSchedule` (check and delete under the lock), `BulkCreateSchedules`, `UpdateScheduleAvailability` (closing a date), `DeleteBlockedDate`, `ToggleServiceStatus`, `UpdateService` (including staff and resource pivots) and `DeleteService`. Payment outcomes and the expiry sweep already lock the tenant through the Payment lifecycle (BQ-067).

**Tests:**
- `tests/Feature/Actions/Schedule/ClaimSlotsTest.php` (11 tests): order; stale reclaim; held slot; rollback of cleanup on refusal; foreign tenant before cleanup; foreign service; closed date; inactive service; past; anomaly not cancelled; listing agrees with claim.
- `ClaimSlotsLockingTest` (`mysql` group): a claim waits on the tenant row. This is the row the sweep holds while it reclaims a payment, which covers "sweep vs claim" from BQ-071.
- `BookingManageTest::test_reschedule_page_does_not_offer_a_date_closed_by_the_owner` (mutation-checked).
- `FiveModulesFullIntegrationTest::test_walkin_for_a_slot_that_already_started_is_refused`. `test_calendar_quick_walkin_booking_creation` now freezes the time at 08:00: its slot is at 10:00 today, so it only passed before 10:00 once walk-ins respect past time.

**Allowlist:** `slot-occupancy` lost the calendar and both reports, and `scope-bypass-reason` lost 3.

**Split out:** availability cache ownership (decision 9) is BQ-075. Recording blocked-date acceptance is BQ-069 as planned.

**Local runs:** SQLite suite 477 tests pass; `tests/Feature/Actions` passes on MySQL 8.4; Pint and Larastan clean (baseline lost 6 entries). GitHub Actions run: pending until pushed.
