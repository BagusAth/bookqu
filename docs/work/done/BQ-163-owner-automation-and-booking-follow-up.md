---
id: BQ-163
title: Isolate scheduled jobs and protect automatic completion boundaries
type: bug-fix
status: done
priority: P1
phase: 4
domain: booking
requirements: [FR-SCHEDULE-015, FR-NOTIFICATION-008, FR-BOOKING-024, FR-BOOKING-025, FR-TEAM-004, NFR-PERF-004, NFR-OBS-003]
invariants: [INV-TEN-01, INV-SCH-01, INV-BOOK-02, INV-BOOK-03, INV-SUB-01]
lane: heavy
approval: tech-lead
decision: D-027
launch-blocker: false
depends-on: [BQ-162]
---

## Problem

An exception stops scheduled work for later tenants (T-11). Slot pickers issue a query per slot, and viewing or closing ended sessions loads every candidate ID (T-13). Booking log timestamps use the database clock's default representation while the slip assumes WIB (T-26).

## Plan

1. T-11: report and isolate each tenant failure across the three jobs. Keep automatic slot entitlement in `EntitlementRules`. Weekly summaries require an effective plan.
2. T-13: use the existing occupancy expressions for slot lists. Replace materialized IDs in counts, process completion in batches of 200, and cap manual submissions at 500 candidates with a result message. Update the weekly summary's count consumer.
3. T-26: let Eloquent write the application timestamp on new booking logs, with `UPDATED_AT = null`. Render log times in WIB. Preserve historical timestamps because the originating database time zone is unknown.
4. T-12: apply D-027 using an activation datetime, backfilled at migration for enabled tenants and renewed when the tenant enables the setting again. Manual completion includes old sessions.

Tech Lead and Product Lead approval confirmed through the developer on 2026-11-19. D-027's primary proposal is the implementation basis.

## Invariants at risk

Keep tenant scope on every query, preserve the only source of occupancy and state-transition rules, and apply entitlement at the existing boundary.

## Tests

Use focused cases for a scheduler that continues after tenant failure, bounded processing with unchanged eligibility, and WIB rendering of a known timestamp. Run related suites before one full pass.

## Docs to update

Update the relevant schedule, booking, notification, or operations descriptions only where approved behavior changes.

## Evidence

Proposed on 2026-11-18, approved through the developer on 2026-11-19.

T-26: local SQLite returned UTC for `CURRENT_TIMESTAMP`; the local MySQL session uses the system zone at UTC+07:00. A fixed offset for all historical logs would therefore give different results depending on where the log was written. The production session time zone has not been verified.

- Full SQLite suite on PHP 8.4.25 with GD passed, 1,020 tests and 6,695 assertions.
- Related MySQL suites passed, 35 tests and 147 assertions. After adding the manual request limit, the batch-limit regression passed on SQLite and MySQL. The query-count test confirms that adding slots does not add per-slot queries.
- PHPStan, changed-file Pint, architecture and documentation guardrails, and `git diff --check` passed.
- Local migration applied to `bookqu`; a read-only check found zero enabled tenants without an activation boundary.
- Six focused regression tests were added. Existing scheduler and weekly-summary fixtures now explicitly establish the activation boundary and effective subscription required by the approved rules.
- Browser and production checks remain part of the release verification.
