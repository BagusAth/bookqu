---
id: BQ-023
title: Run integrity tests on MySQL in CI
type: improvement
status: in-progress
priority: P0
phase: 3
domain: booking
requirements: [FR-BOOKING-018, FR-DATA-006]
invariants: [INV-BOOK-04, INV-BOOK-05]
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-023: Run integrity tests on MySQL in CI

Plan approved via [ADR-0014](../adr/ADR-0014-testing-strategy.md) (`mysql` test group on MySQL 8 in CI, SQLite stays the fast default), 2026-10-30.

## Problem

The suite runs on in-memory SQLite, where `lockForUpdate` is a no-op, so double-booking and payout locking are not proven (2026-10-17 technical audit, remaining item 2).

## Plan

Add a MySQL service to CI and a test group for concurrency/integrity (parallel booking attempts, stale-pending eviction, the unique index); keep SQLite for the fast suite.

As built (branch `phase-3-guardrails`):

- `tests.yml` has two jobs, `tests-sqlite` and `tests-mysql` (MySQL 8.0 service). The MySQL job runs the **whole suite** through `phpunit.mysql.xml` (forced database `bookqu_testing`), not only the group: production runs MySQL, and the whole suite takes about a minute. The workflow is also callable (`workflow_call`) for the deploy gate (BQ-018).
- `phpunit.xml` excludes the `mysql` group; `phpunit.mysql.xml` includes it.
- `tests/Feature/Actions/Booking/CreateBookingLockingTest.php` (`mysql` group): a second connection holds the schedule row lock, and the test asserts that `CreateBooking` waits on the `select … from schedules … for update` query itself. A first version that only expected a timeout still passed with the lock removed, because the booking insert also waits on the foreign key. Asserting the waiting SQL closes that gap (mutation-checked).
- `tests/Feature/Database/ActiveBookingSlotIndexTest.php` (both databases): the `unique_active_booking_slot` index rejects a second active booking and ignores cancelled ones.
- Stale-pending eviction is covered by the existing suite, which now also runs on MySQL. Payment-lifecycle lock tests come with BQ-067; walk-in and reschedule lock tests with BQ-012.

## Evidence

- Local MySQL 8.4.3: `vendor/bin/phpunit -c phpunit.mysql.xml` → 416 tests pass (the whole suite plus the new tests).
- Running the whole suite on MySQL first exposed one defect. `ProductionLogicSpecificationTest::test_9_concurrent_booking_cannot_double_book_slot` asserted on the non-existent column `payments.namapelanggan`. SQLite reads an unknown double-quoted identifier as a string literal, so the assertion was vacuous; it now checks `nama_pembayar`.
- CI had been red on `agathan` since the Boost commit: the lock file needed PHP ≥ 8.4.1 (`symfony/yaml` 8.1) while CI runs 8.3. Fixed by `config.platform.php = 8.3.0` (commit `95c811b`).
- GitHub Actions run: pending until the branch is pushed.
