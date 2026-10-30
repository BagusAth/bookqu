---
id: BQ-018
title: Deploy only after CI passes and back up the database before migrating
type: ops
status: approved
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

_Fill in when done._
