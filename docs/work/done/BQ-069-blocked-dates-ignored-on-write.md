---
id: BQ-069
title: Blocked dates are not enforced when creating or rescheduling a booking
type: bug-fix
status: done
priority: P0
phase: 4
domain: schedule
requirements: [FR-SCHEDULE-005, FR-BOOKING-017]
invariants: [INV-SCH-01]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-012]
---

# BQ-069: Blocked dates are not enforced when creating or rescheduling a booking

Closure policy and plan approved by agathan in [ADR-0005](../../adr/ADR-0005-slot-bookability-module.md), 2026-10-30. Implementation/acceptance remains Phase 4 after BQ-012 and the roadmap gates; the current defect is still open.

## Problem

Blocking a date only inserts an `owner_blocked_dates` row (`UpdateScheduleAvailability:33-37`); schedules on that date stay `tersedia`. Listings hide them, but `CreateBooking` and `RescheduleBooking` never check blocked dates, so a direct request (or a stale page) can book or move a booking onto a closed day. No customer or reschedule test covers blocked dates. Related: audit BUG-T5-08 (customer reschedule ignores blocked dates). Found by the 2026-10-30 architecture review.

## Plan

1. Use BQ-012's ADR-0005 module for blocked-date evaluation at the locked write boundary for new bookings, owner/customer reschedule and walk-ins; remove reliance on selection-page checks for protection.
2. Verify block and unblock participate in the shared tenant-first transaction protocol and invalidate all affected availability cache variants after commit.
3. Preserve existing committed pending/paid reservations on date closure according to ADR-0005; do not introduce automatic cancellation or refund.
4. Add regression coverage for direct requests and stale pages, blocked-date refusals without business mutations, existing-reservation preservation, and listing/cache agreement. Reuse BQ-012's module tests and add the distinct HTTP cases rather than duplicate the rule matrix.
5. Prove claim-versus-closure ordering on MySQL, run affected and full tests, then record evidence and update the schedule domain's current-state description. This item closes only after the defect and acceptance cases are verified.

## Evidence

Fixed by BQ-012's Slot bookability module and verified here (2026-10-31).

**Plan steps:**
1. **Write boundary.** `ClaimSlots` refuses closed dates (`SlotRefusal::DateClosed`) for checkout, both reschedule paths and walk-ins; the selection pages are no longer the protection.
2. **Tenant-first.** Closing and reopening a date (`UpdateScheduleAvailability`, `DeleteBlockedDate`) lock the tenant row first. Both clear every service's availability cache after the transaction commits.
3. **Existing reservations kept.** Closing a date does not cancel or refund existing pending or paid reservations.
4. **Regression tests:**
   - `RF09StabilityAndHardeningTest::test_direct_checkout_onto_a_closed_date_is_refused`: no booking and no payment are created.
   - `BookingManageTest::test_reschedule_onto_a_date_closed_by_the_owner_is_refused`: the booking keeps its schedule.
   - `BookingManageTest::test_reschedule_page_does_not_offer_a_date_closed_by_the_owner` (from BQ-012).
   - `RF09StabilityAndHardeningTest::test_closing_a_date_keeps_existing_reservations`.
   - Module rule: `ClaimSlotsTest::test_refuses_a_date_closed_by_the_owner`. Walk-ins use the same claim.
5. **MySQL ordering.** `ClaimSlotsLockingTest::test_closing_a_date_waits_for_a_claim_in_progress`: a closure waits while a claim holds the tenant row and writes nothing. The reverse direction is `test_claim_waits_for_the_tenant_lock_before_touching_schedules`.

GitHub Actions run https://github.com/BagusAth/bookqu/actions/runs/41096222351 (PR #8): Quality checks, SQLite, MySQL and MariaDB passed.
