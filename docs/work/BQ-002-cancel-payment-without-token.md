---
id: BQ-002
title: Require authorization to cancel a pending payment and cancel it at the gateway
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: payment
requirements: [FR-MANAGE-008, FR-PAYMENT-010, NFR-SEC-008]
invariants: [INV-TOK-01, INV-PAY-03]
launch-blocker: true
needs-lead-approval: true
depends-on: [BQ-028, BQ-067]
---

# BQ-002: Require authorization to cancel a pending payment and cancel it at the gateway

## Problem

`POST …/booking/payment/{order_id}/cancel` (`BookingController::cancelPayment`, line 803) cancels any pending payment of the tenant and its bookings with only the `order_id`. It also never cancels the transaction at SingaPay/Midtrans, so the customer can still pay a cancelled order (audit BUG-T4-01, BQ-049).

> ADR-0004: payer cancel is a Payment lifecycle transition; gateway cancellation is requested after commit (decision 9).

## Plan

1. Require the same authorization as BQ-001.
2. Add a gateway cancellation method to `PaymentGatewayInterface` (or expire the payment link) and call it after commit.
3. Tests for unauthorized cancel and for the gateway cancellation being requested.

## Evidence

_Fill in when done._
