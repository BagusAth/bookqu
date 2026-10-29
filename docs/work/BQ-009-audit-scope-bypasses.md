---
id: BQ-009
title: Audit every withoutGlobalScope(s) usage
type: improvement
status: proposed
priority: P1
phase: 4
domain: tenancy
requirements: [FR-TENANT-002, NFR-SEC-007]
invariants: [INV-TEN-01]
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-009: Audit every withoutGlobalScope(s) usage

## Problem

There are 55 calls of `withoutGlobalScope(s)` in 29 files (largest: `AdminDashboardController` 7, `Service` 4, `SynchronizePaymentStatus` 4). Spot checks (`UpdateService`, `DeleteService`, `DeleteSchedule`) re-filter by tenant, but no inventory proves each one is safe (remaining item 1 of the 2026-10-17 technical audit).

## Plan

List each call with file:line, reason and ownership check; fix or justify; add a short comment at each call; add tests where a bypass guards data access. A parallel (ultracode) review fits this item.

## Evidence

_Fill in when done._
