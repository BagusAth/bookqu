---
id: BQ-073
title: Run the integrity suite on the production database engine
type: ops
status: proposed
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

Found by the G3 run of `test-ssh.yml` (2026-10-30, run 41078956371).

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

_Fill in when done._
