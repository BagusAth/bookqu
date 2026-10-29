---
id: BQ-039
title: Verify and triage the 2026-10 defect audit
type: improvement
status: proposed
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

_Fill in when done._
