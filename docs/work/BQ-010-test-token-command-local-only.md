---
id: BQ-010
title: Restrict booking:generate-test-token to local and testing
type: improvement
status: approved
priority: P1
phase: 4
domain: customer-access
requirements: [NFR-SEC-006]
invariants: [INV-TOK-01]
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-010: Restrict booking:generate-test-token to local and testing

## Problem

`app/Console/Commands/GenerateTestBookingToken.php` creates mock bookings and regenerates the tokens of real bookings (invalidating customers' links) in any environment. Lead approved restricting it to local on 2026-10-30.

## Plan

Abort unless `app()->environment('local', 'testing')`; add a test that it refuses in production.

## Evidence

_Fill in when done._
