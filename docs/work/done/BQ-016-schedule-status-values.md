---
id: BQ-016
title: Align schedule status values with the database enum
type: bug-fix
status: done
priority: P2
phase: 6
domain: schedule
requirements: [FR-SCHEDULE-005]
invariants: [INV-SCH-01]
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-016: Align schedule status values with the database enum

## Problem

`schedules.status` is `enum('tersedia','tidak_tersedia')`, but `AvailabilityRules::getSlotAvailabilityStatus` checks for `diblokir`, which can never be stored; blocking uses `owner_blocked_dates` instead.

## Plan

Remove the dead branch and its unused refusal value. Keep date blocks in `owner_blocked_dates` and preserve both stored slot statuses. Update the glossary and tests.

## Evidence

- `4be0179` removed the unreachable per-slot block and its stale PHPStan baseline entry. `AvailabilityRules` still returns `BLOCKED` for an open slot on a date closed by its tenant.
- The new availability test passed before and after the cleanup. It verifies that another tenant's block has no effect, adding a date block leaves the stored status unchanged, and removing the block makes the slot available again.
- Related SQLite tests passed (41 tests, 176 assertions). `AvailabilityRulesTest` passed on local MySQL (5 tests, 16 assertions).
- The full SQLite suite passed (987 tests, 6,536 assertions). Pint and PHPStan passed on PHP 8.4.25, with CLI OPcache disabled for PHPStan.
