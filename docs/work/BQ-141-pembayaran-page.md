---
id: BQ-141
title: Pembayaran page: summary, transactions, balance and payouts, accounts, explanation
type: feature
status: proposed
priority: P1
phase: 4
domain: balance-payout
requirements: []
invariants: []
lane: light
approval: none
decision: D-004, D-016
launch-blocker: true
depends-on: [BQ-129, BQ-131, BQ-127]
---

# BQ-141: Pembayaran page: summary, transactions, balance and payouts, accounts, explanation

Epic **E4 · Trust and acquisition pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Balance, payouts and transactions are split across the balance page and the hidden payment settings page.

## Plan

1. Build `owner-portal.md` §8.12 tabs; `/owner/settings/balance` redirects.
2. Move the existing payout dialog and accounts (BQ-123 to BQ-125) into the tabs without changing their rules; add the balance ledger.
3. Pemilik only.

## Scope

- **Acceptance criteria owned:** AC-PAY-01, AC-PAY-02, AC-PAY-03, AC-PAY-04, AC-PAY-05.
- **New requirements owned:** none.
- **ADRs:** none.

## Invariants at risk

Balance changes only through the existing payout and ledger actions.

## Tests

- AC-PAY-01 to AC-PAY-05.

## Docs to update

- `docs/domains/balance-payout.md`

## Evidence (fill when done)
