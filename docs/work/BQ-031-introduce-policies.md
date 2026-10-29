---
id: BQ-031
title: Introduce Laravel Policies for owner resources
type: refactor
status: proposed
priority: P2
phase: 6
domain: tenancy
requirements: [NFR-SEC-006]
invariants: [INV-TEN-01]
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-028]
---

# BQ-031: Introduce Laravel Policies for owner resources

## Problem

There is no `app/Policies`; authorization relies on `TenantScope` plus manual checks in each controller or action.

## Plan

Per the ADR: policies for bookings, services, schedules, customers and reviews; controllers call `authorize`; isolation tests per resource.

## Evidence

_Fill in when done._
