---
id: BQ-159
title: Fix the SQLite rollback of the legacy payment token migration
type: bug-fix
status: done
priority: P2
phase: 8
domain: payment
requirements: []
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: []
---

# BQ-159: Fix the SQLite rollback of the legacy payment token migration

Found by the agent session of [BagusAth/bookqu#39](https://github.com/BagusAth/bookqu/pull/39) on 2026-11-04.

## Problem

Rolling back on SQLite fails: the legacy payment token migration drops the `manage_token` column while its unique index still exists. Forward migrations and MySQL/MariaDB are not reported as affected.

## Plan

1. Find the migration and reproduce the failure with a rollback on SQLite.
2. Drop the index before the column in the rollback path.

## Invariants at risk

None; the change affects only the rollback path.

## Tests

A rollback of that migration on SQLite succeeds.

## Docs to update

None.

## Evidence

Disetujui Tech Lead 2026-11-04 untuk sesi agen.
Reproduced the SQLite rollback failure in LegacyManageTokenRollbackTest. The rollback drops the unique index first. SQLite 713 passed; Pint and PHPStan passed.

Closed 2026-11-07. Merged into `integration` in PR #41 (merge commit `0076697`, 2026-11-07); CI green on all four jobs; full suite 777 tests, PHPStan and `composer audit` clean. Fix commit `5189bd6`. Test: LegacyManageTokenRollbackTest.
