---
id: BQ-072
title: Update build tooling with npm security advisories
type: improvement
status: proposed
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

_Fill in when done._
