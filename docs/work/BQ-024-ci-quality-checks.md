---
id: BQ-024
title: Add formatting, dependency and documentation checks to CI
type: improvement
status: proposed
priority: P1
phase: 3
domain: —
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-024: Add formatting, dependency and documentation checks to CI

## Problem

CI only builds and runs tests. Formatting, dependency advisories and documentation integrity are unchecked.

## Plan

Add `vendor/bin/pint --test`, `composer audit`, `npm audit --audit-level=high` (non-blocking at first) and a small script that checks doc links and ID uniqueness (`BQ-`, `INV-`, `FR-`).

## Evidence

_Fill in when done._
