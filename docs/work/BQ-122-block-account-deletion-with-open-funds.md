---
id: BQ-122
title: Block owner account deletion with open balance or in-flight payouts
type: bug-fix
status: proposed
priority: P1
phase: 8
domain: balance-payout
requirements: [FR-AUTH-006, FR-PAYOUT-001, FR-PAYOUT-002, NFR-SEC-005]
invariants: [INV-TEN-01]
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: []
---

# BQ-122: Block owner account deletion with open balance or in-flight payouts

Referenced product requirement: [Revisian BookQu — Alur Withdraw Balance Owner Bisnis (19/10/2026)](https://docs.google.com/document/d/1JfagfHkv2O0O0pboh6Zi05V6VF3EkU7fZqHZSrm36Xo/edit?tab=t.ufh8olyxuj8o).

## Problem

In `app/Actions/Owner/DeleteOwnerAccount.php`, an owner account can be permanently deleted simply by confirming the account email or business name. There is no check on `tenants.saldo_platform` or active `owner_payouts`.

If an owner deletes an account when:
1. `saldo_platform > 0`: the owner abandons their balance, and platform funds remain unreconciled.
2. `saldo_platform < 0`: the tenant deletes their negative balance obligation (e.g. following customer refunds), causing direct financial loss to the platform.
3. In-flight payouts exist (`pending` or `approved`): active payouts are either orphaned or deleted via cascade, breaking admin disbursement processing and auditability.

## Plan

1. In `app/Actions/Owner/DeleteOwnerAccount.php`, prior to executing the deletion database transaction:
   - Check if `$tenant` is present.
   - If `saldo_platform > 0`, throw `ValidationException::withMessages(['confirm_account' => 'Tarik saldo Anda terlebih dahulu sebelum menghapus akun.'])`.
   - If `saldo_platform < 0`, throw `ValidationException::withMessages(['confirm_account' => 'Akun tidak dapat dihapus karena saldo minus. Hubungi admin BookQu.'])`.
   - If the tenant has any `owner_payouts` with status `pending` or `approved`, throw `ValidationException::withMessages(['confirm_account' => 'Masih ada pengajuan withdraw yang sedang diproses.'])`.
2. Frontend: Verify that `resources/views/owner/settings/account.blade.php` properly renders the error message for key `confirm_account`.
3. Documentation: Update `docs/domains/balance-payout.md` to document the invariant and delete guard.

## Invariants at risk

- `INV-TEN-01` & Financial integrity (`FR-PAYOUT-001`, `FR-PAYOUT-002`): Tenants must not be deleted while holding non-zero platform balances or unresolved withdrawal requests.

## Tests

- Deletion is rejected with error `confirm_account` when `saldo_platform > 0`.
- Deletion is rejected with error `confirm_account` when `saldo_platform < 0`.
- Deletion is rejected with error `confirm_account` when any payout is `pending`.
- Deletion is rejected with error `confirm_account` when any payout is `approved`.
- Deletion is permitted when `saldo_platform == 0` and all past payouts are in terminal states (`success`, `failed`, `rejected`).

## Docs to update

- `docs/domains/balance-payout.md` (Data, Known gaps/invariants).

## Evidence (fill when done)

_Fill in when done._
