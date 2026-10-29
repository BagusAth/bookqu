---
id: BQ-022
title: Production payment credentials and webhook registration
type: ops
status: proposed
priority: P0
phase: 4
domain: payment
requirements: [FR-PAYMENT-002, FR-PAYMENT-006]
invariants: [INV-PAY-04]
launch-blocker: true
needs-lead-approval: true
depends-on: [BQ-017]
---

# BQ-022: Production payment credentials and webhook registration

## Problem

Production SingaPay/Midtrans credentials and webhook URLs have not been verified end-to-end.

## Plan

Lead sets the production credentials; register the webhook URLs; run one real low-value transaction per provider and one refund path; record the evidence.

## Evidence

_Fill in when done._
