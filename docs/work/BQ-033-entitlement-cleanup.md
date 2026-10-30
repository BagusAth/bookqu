---
id: BQ-033
title: Route all entitlement checks through EntitlementRules
type: refactor
status: approved
priority: P2
phase: 6
domain: subscription
requirements: [FR-SUB-006]
invariants: [INV-SUB-01]
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-033: Route all entitlement checks through EntitlementRules

Plan approved via [ADR-0011](../adr/ADR-0011-server-side-release-gating.md) (entitlement only in `EntitlementRules`, separate from the release gate), 2026-10-30.

## Problem

The sidebar view composer decides "Pro" with `str_contains(namapaket, 'pro')` (`app/Providers/AppServiceProvider.php:68`); `CheckExpiredSubscriptions` updates statuses with raw queries instead of `SubscriptionState` rules.

## Plan

Use `EntitlementRules` / `SubscriptionState` in both places; tests for the sidebar badge and for expiry.

## Evidence

_Fill in when done._
