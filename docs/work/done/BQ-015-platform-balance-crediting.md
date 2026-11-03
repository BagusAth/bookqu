---
id: BQ-015
title: Define and implement platform balance crediting and payout processing
type: product-change
status: done
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

## Withdraw flow requested by the client (2026-10, "Revisian BookQu", tab 2)

This is part of this item's payout processing; it is not a separate item. What exists today: `RequestOwnerPayout`, `OwnerPayout` (`pending`/`success`/`rejected`), `owner/balance.blade.php`, route `owner.payouts.request`. What is missing:
- the owner's destination bank account;
- an admin review screen with approve/reject and a rejection reason;
- a transfer reference or proof upload;
- the status path `pending → approved → success | failed | rejected`;
- owner notifications on every status change.

Order: lead decides fee and settlement timing → requirement in `docs/requirements.md` → expand migration on `owner_payouts` → admin screen → notifications → tests per transition and per admin/owner permission.

## Evidence

Done 2026-11-01 (session 6). Lead decision: withdraw ships in v1 with a 0% fee.
- Crediting: `TenantBalanceLedger::creditPaidBooking`, called by the booking-group adapter on `ProviderSucceeded`, idempotent via `payments.balance_credited_at`. Refunds of credited payments are debited in `PaymentLifecycle::refundCancelledBooking`.
- Payouts: bank account on request, admin review at `/admin/payouts` (`ProcessOwnerPayout`, `PayoutState`), proof on the private disk, owner database notifications. The `balance` module is released and has a sidebar entry "Saldo & Withdraw".
- Migration `2026_11_01_130000_expand_owner_payouts_and_balance_credit`.
- Tests: `tests/Feature/Payout/OwnerPayoutFlowTest.php` (10). The locked-module example in `ReleaseGateAndHardeningTest` moved from `balance` to `integrations`.
- Known v1 limits: manual transfers; a balance can go negative after a refund.
