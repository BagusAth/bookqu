---
id: BQ-070
title: Owner checkout callback would trust the browser's payment result
type: bug-fix
status: done
priority: P1
phase: 4
domain: subscription
requirements: [FR-PAYMENT-005, FR-SUB-008, NFR-SEC-009]
invariants: [INV-PAY-04]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-067, BQ-032]
---

# BQ-070: Owner checkout callback would trust the browser's payment result

Plan approved via [ADR-0007](../../adr/ADR-0007-provider-outcome-intake.md) decision 5 (callback ignores browser payload, triggers a server query only), 2026-10-30.

## Problem

`OwnerCheckoutController::handleCallback` (lines 242–246) passes the browser's Snap `result` to `syncStatus` when the server status query errors. The path is dead today only because the parameter is declared `?MidtransPaymentService $paymentService = null` (line 221) — Laravel injects `null` for a class parameter with a default. Removing `= null` would let an owner activate a subscription without paying. INV-PAY-04 holds by accident. Found by the 2026-10-30 architecture review (payment explorer).

## Plan

Remove the client-payload branch; the callback only triggers a server-side status check through the Payment lifecycle module (BQ-067) and never accepts client data. Test: a forged callback payload cannot activate a subscription.

## Evidence

Fixed in BQ-032: `OwnerCheckoutController::handleCallback` no longer reads the request body. It calls `PaymentOutcomeIntake::check()`, which only asks the provider server-side. `CheckoutMidtransTest::test_owner_callback_ignores_the_browser_payload` posts a `settlement` result while Midtrans answers `unknown`; the payment stays `pending`.
- GitHub Actions run https://github.com/BagusAth/bookqu/actions/runs/41089021605 (PR #5): all four jobs passed.
