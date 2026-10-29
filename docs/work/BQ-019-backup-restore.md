---
id: BQ-019
title: Automate backups and prove a restore
type: ops
status: proposed
priority: P0
phase: 4
domain: —
requirements: [NFR-REL-004, NFR-REL-005]
invariants: []
launch-blocker: true
needs-lead-approval: true
depends-on: [BQ-018]
---

# BQ-019: Automate backups and prove a restore

## Problem

No backup policy exists for the production database or uploads (`storage/app`).

## Plan

Scheduled database + uploads backup with retention, stored outside the web root; run and record a restore test on staging.

## Evidence

_Fill in when done._
