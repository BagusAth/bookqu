---
id: BQ-002
title: Require authorization to cancel a pending payment and cancel it at the gateway
type: bug-fix
status: approved
priority: P0
phase: 4
domain: payment
requirements: [FR-MANAGE-008, FR-MANAGE-009, FR-MANAGE-010, FR-PAYMENT-010, NFR-SEC-008]
invariants: [INV-TOK-01, INV-PAY-03]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-067, BQ-001, BQ-032]
---

# BQ-002: Require authorization to cancel a pending payment and cancel it at the gateway

Plan approved via ADR-0004/ADR-0006 and [ADR-0007](../adr/ADR-0007-provider-outcome-intake.md) (gateway cancel best-effort after commit), 2026-10-30.

## Problem

`POST …/booking/payment/{order_id}/cancel` (`BookingController::cancelPayment`, line 803) cancels any pending payment of the tenant and its bookings with only the `order_id`. It also never cancels the transaction at SingaPay/Midtrans, so the customer can still pay a cancelled order (audit BUG-T4-01, BQ-049).

> ADR-0004: payer cancel is a Payment lifecycle transition; gateway cancellation is requested after commit (decision 9).

> ADR-0006 settles the customer authorization contract: Checkout or explicit Management access must grant cancellation of this pending booking payment. BQ-001 supplies it. Provider cancellation details remain candidate #4; this item's overall proposed status is retained.

## Plan

1. Require BQ-001/ADR-0006's exact pending-payment cancellation capability and matching family/tenant; revalidate it inside the tenant-first transaction. Retain CSRF and delegate the transition to ADR-0004's Payment lifecycle. Booking-action grants and public identifiers do not authorize this endpoint.
2. Add a gateway cancellation method to `PaymentGatewayInterface` (or expire the payment link) and call it after commit.
3. Tests for identifier-only/wrong-profile/foreign-family/revoked access, terminal/expired payment refusal, authorized whole-group cancellation, and gateway cancellation being requested after commit. Return behavior follows the stored lifecycle outcome; do not trust the browser's result.

## Evidence

_Fill in when done._
