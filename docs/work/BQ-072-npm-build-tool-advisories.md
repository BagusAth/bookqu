---
id: BQ-072
title: Update build tooling with npm security advisories
type: improvement
status: in-progress
priority: P2
phase: 4
domain: —
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: true
depends-on: []
---

# BQ-072: Update build tooling with npm security advisories

Found by the npm audit step added in BQ-024 (2026-10-30).

## Problem

`npm audit` reports 6 advisories (4 high, 2 critical): `concurrently` and `vite` (direct dev dependencies) and the transitive `shell-quote`, `nanoid`, `postcss` and `source-map-js`. All are build- or dev-time tools that never run on the production server, but they do run in CI and on developer machines. npm reports a fix for each.

## Plan

1. Run `npm audit fix` without `--force` and review the lock-file diff; changes must stay within the ranges in `package.json`.
2. `npm run build` and the test suite pass; compare `public/build` output for unexpected changes.
3. Once the audit is clean, make the CI npm audit step blocking (remove `continue-on-error`).

Needs lead approval because it changes dependencies (`AGENTS.md`, "Who decides").

## Evidence

Approved by agathan on 2026-10-30 ("do all of it" for the G3 actions).

- `npm audit fix` without `--force`; `package.json` unchanged, only `package-lock.json` moved within the existing ranges: vite 8.0.10 → 8.3.5 (rolldown 1.0.0-rc.17 → 1.2.16), concurrently 9.2.1 → 9.2.5, shell-quote 1.8.3 → 1.12.0, nanoid 3.3.12 → 3.3.20, postcss 8.5.13 → 8.5.32, source-map-js 1.2.1 → 1.2.2.
- `npm audit`: 0 vulnerabilities. `npm run build` succeeds with the same manifest entries (`resources/css/app.css`, `resources/js/app.js`). SQLite suite: 425 tests pass.
- CI: the npm audit step in the `quality` job is now blocking (`continue-on-error` removed).
- GitHub Actions run: pending until the branch is pushed.
