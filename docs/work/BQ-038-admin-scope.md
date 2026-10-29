---
id: BQ-038
title: Define the platform admin scope
type: product-change
status: proposed
priority: P2
phase: 8
domain: admin
requirements: [FR-ADMIN-001, FR-ADMIN-002, FR-ADMIN-003, FR-ADMIN-004]
invariants: []
launch-blocker: false
needs-lead-approval: true
depends-on: []
---

# BQ-038: Define the platform admin scope

## Problem

The admin portal is a read-only dashboard. Refund processing and payout approval have no admin workflow, so `refunds.status` and `owner_payouts.status` stay `pending`.

## Plan

Lead defines the admin capabilities (payouts, refunds, tenant support); requirements first, then implementation.

## Evidence

_Fill in when done._
