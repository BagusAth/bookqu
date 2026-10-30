---
id: BQ-006
title: Make the subscription middleware fail closed
type: bug-fix
status: approved
priority: P1
phase: 4
domain: subscription
requirements: [FR-SUB-005, NFR-SEC-006]
invariants: [INV-SUB-01]
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-006: Make the subscription middleware fail closed

Plan approved via [ADR-0010](../adr/ADR-0010-authorization-and-tenant-scoping.md) (fail closed everywhere), 2026-10-30.

## Problem

`CheckSubscription` calls `$next($request)` when no tenant id is found (`app/Http/Middleware/CheckSubscription.php:30`). Today the gated routes also sit behind `owner.profile`, so the gap is latent, but any future route that uses `subscription:` alone would be open.

## Plan

Deny (redirect to the subscription page or 403) when no tenant or subscription is resolved; add a test for a gated route without tenant context.

## Evidence

_Fill in when done._
