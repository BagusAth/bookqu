---
id: BQ-030
title: Decompose Customer/BookingManageController
type: refactor
status: in-progress
priority: P2
phase: 6
domain: customer-access
requirements: [NFR-MAINT-001]
invariants: [INV-TOK-01]
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-012, BQ-028]
---

# BQ-030: Decompose Customer/BookingManageController

## Problem

473 lines mixing token resolution, availability SQL, caching and view assembly.

## Plan

Extract token authorization into a dedicated class (per the ADR), use the shared availability service, keep the controller thin; tests stay green.

## Evidence

_Fill in when done._

The management controller delegates grant-bound subject resolution to ResolveManagementTarget and the calendar payload to GetAvailableSchedules. Existing access operations, cache keys, SQL semantics and response shapes are retained. SQLite 713 passed with existing tests; Pint and PHPStan pass, two obsolete baseline blocks removed.
