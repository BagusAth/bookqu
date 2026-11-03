---
id: BQ-016
title: Align schedule status values with the database enum
type: bug-fix
status: proposed
priority: P2
phase: 6
domain: schedule
requirements: []
invariants: [INV-SCH-01]
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-016: Align schedule status values with the database enum

## Problem

`schedules.status` is `enum('tersedia','tidak_tersedia')`, but `AvailabilityRules::getSlotAvailabilityStatus` checks for `diblokir`, which can never be stored; blocking uses `owner_blocked_dates` instead.

## Plan

Remove the dead branch, or add the value through a migration if per-slot blocking is wanted; update the glossary and tests.

## Evidence

_Fill in when done._
