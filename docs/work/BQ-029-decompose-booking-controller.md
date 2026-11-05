---
id: BQ-029
title: Decompose Customer/BookingController
type: refactor
status: in-progress
priority: P2
phase: 6
domain: booking
requirements: [NFR-MAINT-001]
invariants: [INV-BOOK-03, INV-BOOK-06]
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-012, BQ-025]
---

# BQ-029: Decompose Customer/BookingController

Target decided in [ADR-0008](../adr/ADR-0008-booking-checkout-draft.md): Checkout draft module with typed draft, step transitions, session/in-memory store adapters, quote = charge, 2026-10-30.

## Problem

`app/Http/Controllers/Customer/BookingController.php` has 1,034 lines: Midtrans SDK configuration in the constructor, five copies of session/tenant validation, re-implemented quota, blocked-date, occupancy and contiguity checks, and tenant/service caching.

## Plan

Characterization tests → extract a booking-flow session object and step actions → thin controllers per step → remove duplicated rules (use the domain rules) → the architecture-test allowlist shrinks.

## Evidence

Storefront routes now delegate to catalog, schedule, checkout and payment controllers. CheckoutDraftSession keeps the historical session wire format behind a typed draft. Slot validation uses the shared contiguity rule; existing checkout placement, fingerprint, URLs and feature tests remain intact. The payment writer allowlist shrank by two and five old PHPStan baseline entries were removed. SQLite: 712 passed. Pint and PHPStan pass.
MySQL group: 15 tests passed (59 assertions).
