---
id: BQ-103
title: Uncapped balance payout history and missing payout request double-submit protection
type: bug-fix
status: proposed
priority: P1
phase: 8
domain: balance-payout
requirements: [FR-PAYOUT-001, FR-PAYOUT-002, NFR-SEC-005, NFR-UX-005]
invariants: []
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: []
---

# BQ-103: Uncapped balance payout history and missing payout request double-submit protection

Found during the owner UI deep audit on 2026-11-02 in `OwnerBalanceController` and `/owner/balance`.

## Problem

### 1. Hardcoded 10-Row Payout History Without Pagination
In `app/Http/Controllers/Owner/OwnerBalanceController.php:36-37`:
```php
$payoutRequests = $tenant->payoutRequests()
    ->latest()
    ->limit(10)
    ->get();
```
The view `resources/views/owner/balance.blade.php` displays "Riwayat Penarikan Saldo". Because the query is hard-capped at 10 rows with no pagination links, once an active business makes more than 10 payout requests, older payout records become permanently inaccessible in the UI. The owner cannot review historical settlements, disbursement references, or past audit trails.

### 2. Missing Double-Submit Prevention on Payout Form (Money Operation)
In `resources/views/owner/balance.blade.php:40-57`:
- The withdrawal form submits via standard POST to `route('owner.balance.payout')`.
- The submission button ("Ajukan Penarikan") lacks Alpine `x-data` disabled state (`:disabled="submitting"`) or a loading spinner.
- An owner on a mobile connection or slow network clicking the button multiple times can issue duplicate requests in rapid succession. Even with database transaction locking in `RequestOwnerPayout.php`, simultaneous submissions can trigger unhandled concurrency exceptions or multiple pending payout entries if validation passes before balance deduction.

### 3. Bank Account Information Re-entry Burden (Product Decision D-004)
Every single payout submission requires the owner to retype bank name, account number, and account holder name from scratch. There is no option to save or prefill default payout details from the tenant's profile, increasing operational friction and risk of typographical errors in payout destinations.

## Plan

1. Paginate payout requests in `OwnerBalanceController.php` (e.g. `paginate(10)->withQueryString()`) and add pagination links in `balance.blade.php`.
2. Add double-submit prevention to the payout request form (Alpine submit handler disabling the button and showing a processing indicator).
3. Connect with Product Decision `D-004` regarding tenant payout bank account persistence.

## Invariants at risk

Balance deduction and ledger integrity (`INV-TEN-01`, `FR-PAYOUT-001`, `FR-PAYOUT-002`): payouts must never overdraft tenant balance or produce duplicate debits.

## Tests

- Feature test: payout history is paginated and displays pagination navigation when more than 10 records exist.
- Feature test: concurrent or rapid duplicate payout requests are cleanly rejected or handled idempotently without duplicate balance deductions.
- UI check: submit button disables immediately on form submit.

## Docs to update

`docs/domains/balance-payout.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
