---
id: BQ-032
title: Implement the Provider outcome intake (ADR-0007)
type: refactor
status: approved
priority: P0
phase: 4
domain: payment
requirements: [NFR-EXT-001, BR-PAY-001]
invariants: [INV-PAY-04, INV-PAY-05]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-067]
---

# BQ-032: Implement the Provider outcome intake (ADR-0007)

Rescoped and approved as the implementation of [ADR-0007](../adr/ADR-0007-provider-outcome-intake.md) (Provider outcome intake), 2026-10-30: neutral `ProviderOutcome`, one intake, provider-first status checks, gateway cancel, removal of `MidtransPaymentService`/`CheckPaymentStatus`/fast paths, `config/midtrans.php` folded into `config/payment.php`. Moved to Phase 4 because BQ-003 depends on it. Also confirm whether SingaPay offers a server-side status API.

## Problem

`BookingController` configures `Midtrans\Config` in its constructor; `MidtransPaymentService` is used as a generic status facade for every provider; `config/midtrans.php` duplicates `config/payment.php` (read in `BookingController`, `OwnerCheckoutController`, `OwnerIntegrationController`).

## Plan

Remove provider SDK use outside Infrastructure; replace `MidtransPaymentService` with a provider-agnostic service; read all provider settings from `config/payment.php`; tests for both drivers.

## Evidence

_Fill in when done._
