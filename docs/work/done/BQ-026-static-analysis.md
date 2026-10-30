---
id: BQ-026
title: Introduce Larastan with a baseline
type: improvement
status: done
priority: P2
phase: 3
domain: —
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-026: Introduce Larastan with a baseline

Dev dependency approved by agathan with the Phase 3 plan (level 5 with a baseline, CI fails on new errors), 2026-10-30.

## Problem

There is no static analysis. Larastan would be a new dev dependency, which requires lead approval.

## Plan

If approved: install, start at a moderate level with a baseline file, run it in CI, raise the level over time.

## Evidence

- `larastan/larastan` ^3.13 (v3.13.2, with `phpstan/phpstan` 2.3.4) added to `require-dev`; resolves on the PHP 8.3 platform.
- `phpstan.neon`: level 5 over `app/`. `phpstan-baseline.neon` holds the 190 errors that existed on 2026-10-30 (149 entries): 61 `property.notFound`, 28 `nullsafe.neverNull`, 10 `method.nonObject`, 9 `instanceof.alwaysFalse` and smaller groups.
- CI: a Larastan step in the `quality` job (`--error-format=github`, so errors annotate the PR).
- Probed: a new file with a wrong `strlen()` call is reported; after removing it, the analysis is clean.
- The baseline has **not** been triaged. Spot check: `TrialExpiringNotification.php:35` (`translatedFormat()` on string) is a false positive, because `trial_berakhir` is cast to `datetime`. Shrinking the baseline and raising the level belong to Phase 6 (architecture alignment), not to this item.
- GitHub Actions run https://github.com/BagusAth/bookqu/actions/runs/41039402279 (PR #1, 2026-10-30): `Quality checks`, `Tests (SQLite)` and `Tests (MySQL)` passed.
