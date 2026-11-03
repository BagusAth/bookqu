---
id: BQ-030
title: Decompose Customer/BookingManageController
type: refactor
status: proposed
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
