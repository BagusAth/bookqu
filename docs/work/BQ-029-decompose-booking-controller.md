---
id: BQ-029
title: Decompose Customer/BookingController
type: refactor
status: proposed
priority: P2
phase: 6
domain: booking
requirements: [NFR-MAINT-001]
invariants: [INV-BOOK-03, INV-BOOK-06]
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-012, BQ-025]
---

# BQ-029: Decompose Customer/BookingController

## Problem

`app/Http/Controllers/Customer/BookingController.php` has 1,034 lines: Midtrans SDK configuration in the constructor, five copies of session/tenant validation, re-implemented quota, blocked-date, occupancy and contiguity checks, and tenant/service caching.

## Plan

Characterization tests → extract a booking-flow session object and step actions → thin controllers per step → remove duplicated rules (use the domain rules) → the architecture-test allowlist shrinks.

## Evidence

_Fill in when done._
