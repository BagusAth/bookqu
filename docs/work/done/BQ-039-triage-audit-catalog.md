---
id: BQ-039
title: Verify and triage the 2026-10 defect audit
type: improvement
status: done
priority: P1
phase: 4
domain: —
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-039: Verify and triage the 2026-10 defect audit

## Problem

The 140-defect audit (6 stages, 21 P0) was migrated into BQ-040 … BQ-066 without knowing which defects are already fixed. Spot checks on 2026-10-30: BUG-T1-01 fixed, BUG-T1-03 likely fixed, BUG-T5-01 likely fixed, BUG-T6-02 open, BUG-T5-04 likely open.

## Plan

For each migrated item: reproduce or inspect, then mark it `done` (with evidence) or keep it open with a confirmed root cause. A parallel (ultracode) review fits this item.

## Evidence

Done 2026-11-01. The audit catalog was triaged across sessions 1–6 instead of in one pass: P0 items were fixed or closed with evidence in their own files, and P1 items were resolved or re-homed. Still open for launch: BQ-047 (custom-domain back URL on the payment page), BQ-050 (queued mail, session 7), BQ-057/BQ-081 (walk-in). Post-launch: BQ-049, BQ-082.
