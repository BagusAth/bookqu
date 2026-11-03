---
id: BQ-127
title: Fix payout account follow-ups found in the PR #31 audit
type: bug-fix
status: proposed
priority: P2
phase: 8
domain: balance-payout
requirements: [FR-PAYOUT-002, NFR-UX-005]
invariants: [INV-TEN-01]
lane: heavy
approval: tech-lead
decision: D-004
launch-blocker: true
depends-on: [BQ-123, BQ-124, BQ-125]
---

# BQ-127: Payout account follow-ups from the PR #31 audit

Found by the Tech Lead audit of [BagusAth/bookqu#31](https://github.com/BagusAth/bookqu/pull/31) on 2026-11-04. None of these blocked the merge. The item is `launch-blocker: true` because the release policy (D-018) ships only after the redesign is complete, and finding 1 produces a 500 error.

## Problem

1. **A deleted account cannot be added again.** `database/migrations/2026_11_03_000002_create_tenant_payout_accounts_table.php` creates `unique(['idtenant', 'bank_kode', 'nomor_rekening'])`, while `TenantPayoutAccount` uses `SoftDeletes`. After an owner deletes an account, `SaveTenantPayoutAccount` inserts a new row for the same bank and number. The insert violates the unique index, and the owner gets a 500 error instead of a validation message.
2. **The throttle blocks normal use.** `routes/web.php` puts `throttle:3,10` on the whole `payout-accounts` group, including `POST /{account}/default`. For example, adding two accounts and then switching the primary account uses up all three allowed actions in 10 minutes.
3. **Limits are hardcoded instead of read from configuration.**
   - `SaveTenantPayoutAccount` shows the literal message "Maksimal 3 rekening tersimpan." even though the limit comes from `payouts.max_accounts`.
   - `RequestOwnerPayout` and `RequestPayoutRequest` use the literal value `10000`, while `config/payouts.php` defines `min_amount`, which no code reads.
4. **`approved_by` has no foreign key.** `2026_11_03_000004_add_approval_tracking_to_owner_payouts_table.php` adds `approved_by` as a plain `unsignedBigInteger`, unlike `processed_by`.

## Plan

1. **Re-adding a deleted account:**
   - when an owner adds a bank and number that match a soft-deleted row of the same tenant, restore and update that row;
   - when they match a live row, reject the request with a validation error;
   - write this under the tenant lock in `SaveTenantPayoutAccount`.
2. **Throttle:** apply `throttle:3,10` only to create, update and delete. Give `makeDefault` its own, looser limit.
3. **Configured limits:** build the message from `config('payouts.max_accounts')`, and use `config('payouts.min_amount')` in `RequestOwnerPayout` and in `RequestPayoutRequest`, both in the rule and in the message.
4. **Foreign key:** an expand migration adds a foreign key from `approved_by` to `users.id` with `nullOnDelete`, matching `processed_by`.

## Invariants at risk

INV-TEN-01: restoring a soft-deleted row must stay scoped to the locked tenant.

## Tests

Add or extend tests in `tests/Feature/Payout/TenantPayoutAccountTest.php` and `OwnerPayoutFlowTest.php`:

- re-adding a deleted account restores it;
- adding a duplicate of a live account returns a validation error;
- switching the primary account is not limited by the create/update/delete throttle;
- the configured minimum and maximum apply.

## Docs to update

`docs/domains/balance-payout.md`, if the behaviour description changes.

## Evidence (fill when done)
