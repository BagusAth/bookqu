---
id: BQ-141
title: Pembayaran page: summary, transactions, balance and payouts, accounts, explanation
type: feature
status: done
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

Epic **E4 · Trust and acquisition pages** of the owner portal redesign, planned in [BQ-126](../BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

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

## Evidence

Closed 2026-11-12. Merged into `integration` in PR #45 (merge commit `cd41f24`, 2026-11-10) after the Tech Lead audit. Before the merge: full suite 897 tests on SQLite, the item tests also on MySQL 8, Pint and PHPStan clean. Commit `eac976f`. Tests: `Owner/PembayaranPageTest` (AC-PAY-01 to AC-PAY-05), `Payout/*` tests on the new page and redirects.
## Implementation notes

- **Wave:** built ahead of wave 4 under the ROADMAP rule. It depends on BQ-129, BQ-131 and BQ-127 and touches no file of Developer A's open items.
- **Page:** `owner.payments` has the tabs Ringkasan, Transaksi, Saldo & Pencairan, Rekening and Pengaturan (`Actions/Owner/GetPaymentsPage`). `/owner/settings/balance` answers 301 to the Saldo tab with the query kept. Payout and account writes redirect to their tab.
- **Ledger (AC-PAY-02):** `Actions/Owner/GetBalanceLedger` derives the movements from the four places that change `saldo_platform`: payment credit, refund debit, payout hold and payout return. There is no new table and no schema change. The local seeder writes `saldo_platform` directly, so seeded tenants show a difference.
- **Request dialog:** three steps (amount, account, confirmation). It keeps the request key, the single-submit guard and the server rules of `RequestOwnerPayout`. Eligibility reasons show before the dialog can open.
- **Accounts:** the existing actions, the password check and the throttles are unchanged. The account count follows `payouts.max_accounts` (AC-PAY-04), and the edit and delete URLs now come from named route templates.
- **D-016, AC-PAY-05:** removed the routes `owner.settings.payment`, `owner.settings.payments` and `owner.settings.payment-setting`, `OwnerPaymentSettingsController` and the `payment-settings` view.
  - `OwnerSettingController::updatePaymentSettings`, `UpdatePaymentSettingsRequest` and `Actions/Tenant/UpdatePaymentSettings` are left in place. `OwnerSettingController` is part of BQ-144's open work, and the action is covered by `TenantGatewayCredentialsTest`.
  - Removing that backend, and `payment_mode = owner` at checkout, is a follow-up for the Tech Lead.
- **Links moved to the new page:** the integrations page and the payout notifications now point to `owner.payments`.
- **Tests changed by the spec:**
  - payout and account tests assert the new redirect targets and the new copy ("Riwayat pencairan", "Belum dapat mengajukan pencairan");
  - `SettingsTest` now asserts that the gateway form can no longer be saved;
  - `EndedSubscriptionTest` opens `/owner/payments`;
  - `OwnerSidebarNavigationTest` checks the removed URL by path.
