---
id: BQ-032
title: Clean up the payment boundary
type: refactor
status: proposed
priority: P2
phase: 6
domain: payment
requirements: [NFR-EXT-001]
invariants: [INV-PAY-05]
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-028]
---

# BQ-032: Clean up the payment boundary

## Problem

`BookingController` configures `Midtrans\Config` in its constructor; `MidtransPaymentService` is used as a generic status facade for every provider; `config/midtrans.php` duplicates `config/payment.php` (read in `BookingController`, `OwnerCheckoutController`, `OwnerIntegrationController`).

## Plan

Remove provider SDK use outside Infrastructure; replace `MidtransPaymentService` with a provider-agnostic service; read all provider settings from `config/payment.php`; tests for both drivers.

## Evidence

_Fill in when done._
