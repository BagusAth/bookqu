---
id: BQ-025
title: Enforce architecture rules with tests
type: improvement
status: proposed
priority: P1
phase: 3
domain: —
requirements: []
invariants: [INV-BOOK-03, INV-PAY-05, INV-SUB-01]
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-028]
---

# BQ-025: Enforce architecture rules with tests

## Problem

The rules in `docs/architecture.md` are only written down; violations already exist (see "Known gaps" in `docs/domains/`).

## Plan

PHPUnit tests that scan the code with an allowlist of known violations that may only shrink: no `Midtrans\` outside Infrastructure, no `App\Models` or `DB::` in Blade, no `subMinutes(15)` / occupancy status lists outside `BookingRules`, no plan-name checks outside `EntitlementRules`, Tier 3 routes behind the gate.

## Evidence

_Fill in when done._
