---
id: BQ-008
title: Disable Tier 3 modules on the server, not only in navigation
type: improvement
status: proposed
priority: P1
phase: 4
domain: staged-modules
requirements: [NFR-SEC-006]
invariants: []
launch-blocker: true
needs-lead-approval: true
depends-on: [BQ-028]
---

# BQ-008: Disable Tier 3 modules on the server, not only in navigation

## Problem

ADR-0002 hides Tier 3 modules from navigation, but every endpoint still works by URL: vouchers, additional items, staff & resources, appearance, direct payment settings (`POST /owner/settings/payment` stores gateway keys and can switch `payment_mode`), assets, landing-page builder (`docs/domains/staged-modules.md`).

## Plan

1. Choose the gating mechanism in an ADR (config + middleware, or Laravel Pennant) — BQ-028.
2. Put every Tier 3 route group behind it; keep Tier 2 pages reachable but locked where required.
3. Tests: Tier 3 routes return 404/403 when disabled; `OwnerSidebarNavigationTest` still passes.

## Evidence

_Fill in when done._
