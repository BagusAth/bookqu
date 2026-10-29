---
id: BQ-023
title: Run integrity tests on MySQL in CI
type: improvement
status: proposed
priority: P0
phase: 3
domain: booking
requirements: [FR-BOOKING-018, FR-DATA-006]
invariants: [INV-BOOK-04, INV-BOOK-05]
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-023: Run integrity tests on MySQL in CI

## Problem

The suite runs on in-memory SQLite, where `lockForUpdate` is a no-op, so double-booking and payout locking are not proven (2026-10-17 technical audit, remaining item 2).

## Plan

Add a MySQL service to CI and a test group for concurrency/integrity (parallel booking attempts, stale-pending eviction, the unique index); keep SQLite for the fast suite.

## Evidence

_Fill in when done._
