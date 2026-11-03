---
id: BQ-031
title: Introduce Laravel Policies for owner resources
type: refactor
status: approved
priority: P2
phase: 6
domain: tenancy
requirements: [NFR-SEC-006]
invariants: [INV-TEN-01]
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-009]
---

# BQ-031: Introduce Laravel Policies for owner resources

Plan approved via [ADR-0010](../adr/ADR-0010-authorization-and-tenant-scoping.md) (owner Policies, foreign ids → 404, TenantScope as defence in depth), 2026-10-30.

## Problem

There is no `app/Policies`; authorization relies on `TenantScope` plus manual checks in each controller or action.

## Plan

Per the ADR: policies for bookings, services, schedules, customers and reviews; controllers call `authorize`; isolation tests per resource.

## Evidence

_Fill in when done._
