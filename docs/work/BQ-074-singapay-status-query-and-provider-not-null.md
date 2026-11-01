---
id: BQ-074
title: Confirm a SingaPay status query and make payments.provider NOT NULL
type: improvement
status: proposed
priority: P1
phase: 4
domain: payment
requirements: [NFR-EXT-001]
invariants: [INV-PAY-04, INV-PAY-05]
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-032]
---

# BQ-074: Confirm a SingaPay status query and make payments.provider NOT NULL

Found while implementing BQ-032 (2026-10-30).

## Problem

1. **No status query for SingaPay.** `SingaPayClient` has no transaction or payment-link status endpoint, so `SingaPayPaymentGateway::queryOutcome()` answers `unknown` (ADR-0007 decision 4). If SingaPay never delivers a webhook, the payment expires locally. A later success is then only recorded as a late success (refund owed) if SingaPay retries the webhook. Whether SingaPay's API offers a status endpoint is not confirmed.
2. **`payments.provider` is still nullable.** Migration `2026_10_31_000001_backfill_payment_provider` backfilled legacy rows, and `PaymentRules::providerOf()` still infers legacy values. The contract step (NOT NULL and dropping the inference) belongs to a later release (expand/contract, `docs/operations.md` §3).

## Plan

1. Confirm with SingaPay documentation or support whether a status endpoint exists. If it does, implement `queryOutcome()` with contract tests from recorded responses. If it does not, record that in `docs/domains/payment.md`.
2. After one release with the backfill in production: make `payments.provider` NOT NULL, remove the legacy inference from `PaymentRules::providerOf()`, and verify that no row is null before migrating.

Needs lead approval: step 1 needs vendor contact, and step 2 is a schema change.

## Evidence

Lead decision 2026-11-01: v1 ships without a SingaPay status query and relies on webhooks (SingaPay retries), the simulator and BQ-078 logging. Bagus asks SingaPay about a status API.

**Step 1 done (2026-11-01, lead reopened it the same day).** The sandbox API answers two read endpoints with the existing credentials:
- `GET /api/v1.0/payment-link-manage/{account_id}/{id}`: `current_usage` becomes 1 once paid (`status` changes from `open` to `closed`). An expired, unpaid link still reports `status: open`, so the status word alone is not used.
- `GET /api/v1.0/payment-link-histories/{account_id}?page=&per_page=`: one entry per payment with `payment_link_reff_no` (= BookQu `order_id`), `amount` and `status: paid`. The endpoint ignores filter parameters.

The endpoints were found in the community SDK `aliziodev/laravel-singapay`, then confirmed against the sandbox. `SingaPayPaymentGateway::queryOutcome()` now reports `sukses` from the paid history, and `unknown` otherwise (see `docs/domains/payment.md`).

- `tests/Feature/Payment/SingaPayStatusQueryTest.php` uses recorded sandbox responses. It covers: a paid history settles the booking group, an unused link is `unknown` and throttled, a different amount is rejected, and another order's link or a missing history, link id or provider is `unknown`.
- Sandbox run: order `BKG-6-1793535530-174` was paid at SingaPay but `pending` locally. One `PaymentOutcomeIntake::check()` made it `sukses` and its booking `paid`. An unpaid order stayed `unknown`.

Step 2 (`payments.provider` NOT NULL) stays open until one release after the backfill.
