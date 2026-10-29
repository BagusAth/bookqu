---
id: BQ-015
title: Define and implement platform balance crediting and payout processing
type: product-change
status: proposed
priority: P1
phase: 4
domain: balance-payout
requirements: [FR-PAYOUT-001, FR-PAYOUT-002, FR-PAYOUT-003]
invariants: []
launch-blocker: true
needs-lead-approval: true
depends-on: [BQ-067]
---

# BQ-015: Define and implement platform balance crediting and payout processing

## Problem

`tenants.saldo_platform` is only debited by `RequestOwnerPayout`; nothing credits it when booking payments succeed, and no admin flow processes `owner_payouts` (status stays `pending`). Under the platform escrow model tenants cannot receive their money through the product.

> ADR-0004 (decision 10): balance crediting stays out of BQ-067; the booking-group adapter of the Payment lifecycle module is its hook once fees and settlement are decided.

## Plan

Lead defines fee, settlement timing and payout approval (product change). Then credit the balance atomically and idempotently in `SynchronizePaymentStatus` on booking success, handle refunds, add an admin payout screen, and test it. Alternative: hide payouts until this is defined.

## Evidence

_Fill in when done._
