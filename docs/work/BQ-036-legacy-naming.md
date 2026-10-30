---
id: BQ-036
title: Execute the legacy naming strategy
type: refactor
status: rejected
priority: P2
phase: 6
domain: —
requirements: [NFR-MAINT-004]
invariants: []
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-028]
---

# BQ-036: Execute the legacy naming strategy

Rejected as a pre-launch rename by [ADR-0012](../adr/ADR-0012-module-boundaries-and-naming.md), 2026-10-30: legacy table/column/route names stay, the glossary maps them, and new code uses canonical English. Any schema rename becomes a new Phase 8 item with an expand/contract plan.

## Problem

Tables, columns, routes and views use Indonesian legacy names (`docs/glossary.md`).

## Plan

Decided in ADR-0012: keep legacy storage names with the glossary; no pre-launch rename.

## Evidence

_Fill in when done._
