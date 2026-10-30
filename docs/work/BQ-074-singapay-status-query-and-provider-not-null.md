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

_Fill in when done._
