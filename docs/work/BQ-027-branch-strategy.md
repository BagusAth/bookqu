---
id: BQ-027
title: Decide the branch strategy and clean up old branches
type: ops
status: proposed
priority: P2
phase: 7
domain: —
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: true
depends-on: []
---

# BQ-027: Decide the branch strategy and clean up old branches

Strategy decided in [ADR-0015](../adr/ADR-0015-environments-and-release.md): protect `main`, `agathan` as integration branch, `archive/<name>` tags before deleting merged branches. Execution and the unmerged branches remain the lead's decision.

## Problem

15 branches exist; `agathan` is 22 commits ahead of `main`; `main` auto-deploys. Merged and inactive: `payment`, `mergeV2`, `mergeV3`, `bagus`, `mergeV2-development`, `testing-agathan`, `Refactor`. Unmerged work: `new-ui` (2 commits), `scalev-integration` (1), `owner` (1, from May).

## Plan

Lead decides: (a) protect `main` (PR + green CI); (b) the integration branch name; (c) archive tags, then delete merged branches; (d) ask the owners about unmerged ones. Then update `docs/workflow.md` §5.

## Evidence

_Fill in when done._
