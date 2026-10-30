---
id: BQ-018
title: Deploy only after CI passes and back up the database before migrating
type: ops
status: in-progress
priority: P0
phase: 3
domain: —
requirements: [NFR-REL-004]
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-018: Deploy only after CI passes and back up the database before migrating

Plan approved via [ADR-0015](../adr/ADR-0015-environments-and-release.md) (gated deploy, `mysqldump` before migrate, maintenance mode, expand/contract migrations), 2026-10-30.

## Problem

`deploy-production.yml` runs on push to `main` independently of `tests.yml`; `php artisan migrate --force` runs without a database backup, and the rollback restores code but not schema or data.

## Plan

Make the deploy job depend on the test job (one workflow with `needs:`, or `workflow_run` on success); add a gzipped, timestamped `mysqldump` (permission 600) before `migrate`; document the restore in `docs/operations.md`.

## Evidence

- `deploy-production.yml`: a `tests` job calls `tests.yml` (`workflow_call`); `deploy` has `needs: tests`, so it runs only after the quality, SQLite and MySQL jobs pass.
- Before the code swap the server dumps the database (`db-backups/bookqu-<timestamp>.sql.gz`, directory 700, file 600, 14-day retention, credentials in a temporary 600 client file written from the live Laravel config). An incomplete dump stops the deploy before anything changes. `migrate` runs inside `artisan down`/`up`; the failure trap also brings the site back up and prints where the DB backup is. Code backups older than 14 days are now removed too.
- Restore procedure in `docs/operations.md` §6.
- Verified locally: the extracted script passes `bash -n` and both workflows parse as YAML. The backup block, run against local MySQL 8.4, produced a dump of 33 tables, passed its own checks and removed the client file. Restoring that dump into a scratch database gave 33 tables and the same 41 migration rows; the scratch database was then dropped.
- **Not yet verified on HostData:** whether `mysqldump` exists there (run the manual `test-ssh.yml`, which now reports it), and the first real gated deploy. Both happen at the first release to `main` (lead action).
