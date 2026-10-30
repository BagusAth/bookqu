---
id: BQ-073
title: Run the integrity suite on the production database engine
type: ops
status: done
priority: P1
phase: 4
domain: —
requirements: []
invariants: [INV-BOOK-04, INV-BOOK-05]
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-023]
---

# BQ-073: Run the integrity suite on the production database engine

Found by the G3 run of `test-ssh.yml` (2026-10-30, run 41078956371). Approved by agathan on 2026-10-30.

## Problem

The HostData `mysqldump` is the MariaDB client (`mysqldump from 11.4.10-MariaDB, client 10.19`), so production most likely runs MariaDB 11.4, not MySQL. CI (BQ-023, ADR-0014) and local tests prove locks, unique indexes and concurrency on MySQL 8. MariaDB differs in places that matter here: lock-wait error messages, gap locking details, JSON handling and some DDL. The schedule-locking test asserts on MySQL's error text and SQL, so a green MySQL run does not prove the same behaviour on production.

## Plan

1. Confirm the server engine and version (`SELECT VERSION()` over the SSH workflow or the hosting panel); record it in `docs/operations.md` §1.
2. If it is MariaDB: run the `tests-mysql` job against `mariadb:11.4` as well (matrix), or instead of MySQL 8; adjust tests that depend on MySQL-only error text so both engines are proven.
3. Amend ADR-0014 ("MySQL 8") to name the engine actually used in production.

Needs lead approval because it changes the testing strategy in ADR-0014.

## Invariants at risk

INV-BOOK-04/05 (no double booking): the guarantees rest on row locks and the `unique_active_booking_slot` index behaving as tested.

## Tests

The existing `mysql` group and the whole suite on the production engine.

## Docs to update

`docs/adr/ADR-0014-testing-strategy.md`, `docs/architecture.md` §12, `docs/operations.md` §1.

## Evidence (fill when done)

- Engine confirmed: `test-ssh.yml` (which now prints the server version) reported `11.4.10-MariaDB-cll-lve`, run https://github.com/BagusAth/bookqu/actions/runs/41081391576. Recorded in `docs/operations.md` §1.
- CI: `tests-mysql` is a matrix of MySQL 8.0 and MariaDB 11.4 (`Tests (MySQL)`, `Tests (MariaDB)`). Branch protection on `main` must require both.
- The first MariaDB run failed 10 tests, all from one query: the owner customer list (`Owner/OwnerCustomerController::index`) returned 500 because MariaDB's `ONLY_FULL_GROUP_BY` rejects selecting the identifier expression that is only repeated in `GROUP BY` (error 1055). **Production is affected the same way** (`strict` mode is on), so `/owner/customers` most likely fails there today. The `mysql` lock group passed on MariaDB unchanged.
- Fix: the list and the upcoming-booking queries compute the identifier in a derived table and group by that column. New test `test_upcoming_booking_is_the_earliest_future_booking` covers the second query, which had no test.
- ADR-0014, `docs/architecture.md` §12 and `.ai/rules/tests.md` now name MariaDB 11.4.
- Local: SQLite and MySQL 8.4 pass for `CustomerManagementTest` (19 tests); Pint and Larastan clean. GitHub Actions run https://github.com/BagusAth/bookqu/actions/runs/41082279757: all four jobs passed, including `Tests (MariaDB)`.
