---
id: BQ-075
title: Slot bookability owns availability cache keys and invalidation
type: refactor
status: approved
priority: P2
phase: 6
domain: schedule
requirements: []
invariants: [INV-SCH-01]
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-012]
---

# BQ-075: Slot bookability owns availability cache keys and invalidation

Split from BQ-012 (2026-10-30). The plan is approved in ADR-0005 decision 9.

## Problem

`ClearsBookingCache` and `ScheduleAvailabilityCache` are called by booking actions, payment adapters, schedule and service actions and controllers. Each caller decides which keys to clear. A missed variant (date range, reschedule exclusion) can serve stale availability for up to the cache lifetime. Claims never trust the cache, so correctness holds, but listings can lag.

## Plan

1. Move key construction and invalidation behind the Slot bookability module (`ClaimSlots` and its reader side). Callers report "these dates and services changed"; the module invalidates every affected variant after commit.
2. Cache only supporting data, never clock-dependent final booleans (decision 8).
3. Test cache behaviour through the module: range, exclusion, invalidation after commit, time recomputation.

## Evidence

_Fill in when done._
