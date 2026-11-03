---
id: BQ-103
title: Uncapped balance payout history and missing payout request double-submit protection
type: bug-fix
status: done
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
Referenced product requirement: [Revisian BookQu — Alur Withdraw Balance Owner Bisnis (19/10/2026)](https://docs.google.com/document/d/1JfagfHkv2O0O0pboh6Zi05V6VF3EkU7fZqHZSrm36Xo/edit?tab=t.ufh8olyxuj8o).

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

### 2. Missing Double-Submit Prevention and Server-Side Idempotency on Payout Form (Money Operation)
In `resources/views/owner/balance.blade.php:40-57`:
- The withdrawal form submits via standard POST to `route('owner.balance.payout')`.
- The submission button ("Ajukan Penarikan") lacks Alpine `x-data` disabled state (`:disabled="submitting"`) or a loading spinner.
- An owner on a mobile connection or slow network clicking the button multiple times can issue duplicate requests in rapid succession. Even with database transaction locking in `RequestOwnerPayout.php`, simultaneous submissions can trigger unhandled concurrency exceptions or multiple pending payout entries if validation passes before balance deduction.
- Payout requests lack an idempotent request key. Network retries can cause double debiting if the server processes the same intent twice.

### 3. Bank Account Information Re-entry Burden (Product Decision D-004)
Every single payout submission requires the owner to retype bank name, account number, and account holder name from scratch. There is no option to save or prefill default payout details from the tenant's profile, increasing operational friction and risk of typographical errors in payout destinations (handled comprehensively in follow-up BQ-123).

## Plan

1. **Database Schema:**
   - Migration `add_request_key_to_owner_payouts`: add `uuid('request_key')->nullable()` and unique composite index `(idtenant, request_key)` to `owner_payouts`.
2. **Server-Side Idempotency in Action:**
   - Update `RequestOwnerPayout::execute(...)` to accept `string $requestKey`.
   - Inside the transaction under the tenant row lock: if an `OwnerPayout` with the same `(idtenant, request_key)` already exists, return that existing payout immediately without deducting balance again.
   - Attach `$requestKey` to any newly created `OwnerPayout`.
3. **Form Request Validation:**
   - Update `RequestPayoutRequest` to require `request_key` with rules `required|uuid`.
4. **Controller & Pagination:**
   - In `OwnerBalanceController@index`: query `OwnerPayout::where('idtenant', $tenant->id)->latest('requested_at')->paginate(15)->withQueryString()`.
   - Pass a fresh UUID `requestKey = (string) Str::uuid()` to the view.
5. **Frontend Form & Table Polish (`resources/views/owner/balance.blade.php`):**
   - Add hidden input `request_key` with value `old('request_key', $requestKey)`.
   - Equip form with Alpine `x-data="{ submitting: false }"` and `@submit="submitting = true"`; bind button `:disabled="submitting"` with text indicator "Memproses…".
   - Render pagination links below the history table.
   - Update table title to "Riwayat Withdraw" (removing "Terbaru").

## Invariants at risk

Balance deduction and ledger integrity (`INV-TEN-01`, `FR-PAYOUT-001`, `FR-PAYOUT-002`): payouts must never overdraft tenant balance or produce duplicate debits upon concurrent or repeated submissions.

## Tests

- Feature test: 16 payout requests result in page 2 containing exactly 1 row with working pagination links.
- Feature test: two identical POST requests with the same `request_key` return the same payout record, and tenant balance is debited only once.
- Feature test: POST requests with different `request_key` values produce distinct payout records (prior to BQ-124 `max_open` constraint).
- UI check: submit button disables immediately on form submit.

## Docs to update

`docs/domains/balance-payout.md` (Known gaps).

## Evidence (fill when done)

- Migration `2026_11_03_000001_add_request_key_to_owner_payouts_table` applied with unique index `(idtenant, request_key)`.
- `RequestOwnerPayout::execute(...)` checks for existing payout with the same `(idtenant, request_key)` under tenant row lock, returning it without re-deducting balance.
- `RequestPayoutRequest` validates `request_key` as `required|uuid`.
- `OwnerBalanceController@index` paginates history to 15 rows using `latest('requested_at')->paginate(15)->withQueryString()` and supplies fresh UUID `$requestKey`.
- `resources/views/owner/balance.blade.php` embeds hidden `request_key` input, disables button on submit with Alpine `submitting` flag, displays "Riwayat Withdraw" header, and renders pagination links.
- Feature tests in `tests/Feature/Payout/OwnerPayoutFlowTest.php`:
  - `test_payout_history_is_paginated_to_15_records_per_page`: 16 records split across page 1 (15 rows) and page 2 (1 row).
  - `test_payout_request_is_idempotent_with_same_request_key`: repeat POST with same UUID returns existing payout and debits balance once.
  - `test_payout_requests_with_different_request_keys_create_distinct_payouts`: distinct UUIDs create distinct records and debit balance accordingly.
  - `test_balance_view_renders_request_key_and_alpine_double_submit_protection`: verifies view elements.
- All related test suites passing.
