---
id: BQ-123
title: Saved tenant payout bank accounts and destination selection
type: feature
status: done
priority: P1
phase: 8
domain: balance-payout
requirements: [FR-PAYOUT-002, NFR-SEC-005, NFR-UX-005]
invariants: [INV-TEN-01]
lane: heavy
approval: tech-lead
decision: D-004
launch-blocker: false
depends-on: [BQ-103]
---

# BQ-123: Saved tenant payout bank accounts and destination selection

Referenced product requirement: [Revisian BookQu — Alur Withdraw Balance Owner Bisnis (19/10/2026)](https://docs.google.com/document/d/1JfagfHkv2O0O0pboh6Zi05V6VF3EkU7fZqHZSrm36Xo/edit?tab=t.ufh8olyxuj8o) (Langkah 3) and Product Decision [D-004](../product-decisions.md).

## Problem

Currently, an owner must manually enter their bank name, account number, and account holder name on every single withdrawal request. There is no destination verification, high risk of typos, no ability to save multiple bank accounts, and no security confirmation (such as password verification or notification) when destination details are altered.

## Plan

1. **Configuration:**
   - Create `config/banks.php`: supported banks map `kode => nama` (BCA, BRI, BNI, MANDIRI, BSI, CIMB, PERMATA, DANAMON, BTN, OCBC, MAYBANK, SEABANK, JAGO, BLU).
   - Create `config/payouts.php`: settings for `min_amount` (10000), `max_open` (1), `max_amount_per_request` (null), `max_requests_per_day` (null), and `max_accounts` (3), reading from `.env`. Add corresponding entries to `.env.example`.
2. **Database Migrations:**
   - Create `tenant_payout_accounts` table: `id`, `idtenant` (FK cascade to `tenants`), `bank_kode` (string 20), `bank_nama` (string 100), `nomor_rekening` (string 30), `nama_pemilik_rekening` (string 150), `is_default` (boolean default false), `changed_at` (timestamp, set on creation or bank/number/holder change, unchanged on default toggle), timestamps, softDeletes, unique `(idtenant, bank_kode, nomor_rekening)`.
   - Update `owner_payouts` table: add `payout_account_id` (foreignId nullable, constrained to `tenant_payout_accounts`, nullOnDelete) and `bank_kode` (string 20 nullable). Retain legacy snapshot columns (`bank_nama`, `nomor_rekening`, `nama_pemilik_rekening`) so historical payouts never mutate if an account is changed or deleted.
3. **Models & DTOs:**
   - Model `App\Models\TenantPayoutAccount` with `BelongsToTenant`, `SoftDeletes`, `tenant()` relation, and `masked_number` attribute ("••••" + last 4 digits).
   - Add relations: `Tenant::payoutAccounts()` and `OwnerPayout::payoutAccount()` (`withTrashed()`).
   - DTO `App\Domain\Payout\PayoutAccountData` (readonly: `bankKode`, `nomorRekening`, `namaPemilik`).
4. **Domain Actions:**
   - `Owner/SaveTenantPayoutAccount`: executed inside a database transaction with tenant row lock; enforce `max_accounts` (3); lookup `bank_nama` from `config/banks.php`; mark as default if it is the tenant's first account; record `changed_at`; dispatch `PayoutAccountChangedNotification` after commit.
   - `Owner/DeleteTenantPayoutAccount`: refuse deletion if account is referenced by an active payout (`pending` or `approved`); soft-delete; if it was default, promote the newest remaining account to default.
   - `Owner/MakeDefaultPayoutAccount`: atomically clear previous default and set chosen account as default.
5. **HTTP Controller & Requests:**
   - `Owner/OwnerPayoutAccountController` (store, update, destroy, makeDefault) with `StorePayoutAccountRequest` and `UpdatePayoutAccountRequest`.
   - Validate `bank_kode` (`Rule::in(array_keys(config('banks')))`), `nomor_rekening` (`regex:/^[0-9]{5,30}$/`), `nama_pemilik_rekening` (max 150), and require `current_password` on store, update, and destroy.
   - Route model binding scoped to tenant (`{account}`) returning 404 for cross-tenant access.
   - Routes in owner group with `throttle:3,10`:
     - `POST /owner/payout-accounts` (`owner.payout-accounts.store`)
     - `PUT /owner/payout-accounts/{account}` (`owner.payout-accounts.update`)
     - `DELETE /owner/payout-accounts/{account}` (`owner.payout-accounts.destroy`)
     - `POST /owner/payout-accounts/{account}/default` (`owner.payout-accounts.default`)
6. **Notifications:**
   - `App\Notifications\PayoutAccountChangedNotification`: queued mail implementing `ShouldQueue` + `afterCommit()`, informing owner: "Rekening penarikan [ditambahkan/diubah/dihapus]: {bank} ••••{4 digit}. Jika ini bukan Anda, segera ganti password dan hubungi admin BookQu."
7. **Payout Flow Integration:**
   - `RequestPayoutRequest`: replace bank details with `payout_account_id` (required, exists in `tenant_payout_accounts` for active tenant).
   - `RequestOwnerPayout`: accept `TenantPayoutAccount`, reload account under tenant lock, copy snapshot attributes and foreign key.
   - Move payout submission action from `OwnerSettingController::requestPayout` to `OwnerBalanceController@store` (`owner.payouts.request`).
8. **Frontend (Owner Balance Page):**
   - New "Rekening Penarikan" section in `resources/views/owner/balance.blade.php`: account cards with bank name, masked number, owner name, "Utama" badge, and actions (Jadikan utama, Ubah, Hapus with password confirmation modal). Empty state: "Belum ada rekening. Tambahkan rekening untuk mulai withdraw."
   - Modal dialog for add/edit account: bank select, numeric account number, owner name with hint, and current password. Automatically reopens on validation error.
   - Intermediate payout form selector: replace text fields with saved accounts radio list.
9. **Admin Portal:**
   - In `resources/views/admin/payouts.blade.php`: display badge "Rekening baru (< 24 jam)" if `payoutAccount.changed_at` is less than 24 hours prior to `requested_at`.

## Invariants at risk

- `INV-TEN-01` (Tenant isolation): Payout accounts must strictly belong to the authenticated owner's tenant. No tenant can see, modify, delete, or withdraw funds to another tenant's payout account.
- Snapshot immutability: Historical payout records must remain immutable even if the underlying `TenantPayoutAccount` is edited or soft-deleted.

## Tests

- CRUD operations on payout accounts succeed with valid data and correct `current_password`.
- Store, update, and delete are rejected when `current_password` is incorrect or missing.
- Attempting to add a 4th account is rejected with error "Maksimal 3 rekening tersimpan."
- Attempting to view, update, delete, set default, or withdraw to another tenant's account returns 404.
- Attempting to delete a payout account currently attached to an in-flight payout (`pending` or `approved`) is rejected with error "Rekening sedang dipakai pengajuan withdraw yang berjalan."
- Editing or deleting a payout account does not alter bank details snapshot in historical `owner_payouts`.
- Queued email notification is dispatched on account addition, modification, and deletion.
- Admin view displays "Rekening baru (< 24 jam)" badge when `changed_at` was recent.

## Docs to update

- `docs/domains/balance-payout.md`
- `docs/domains/notifications.md`
- `docs/requirements.md` (FR-PAYOUT-002)

## Evidence (fill when done)

- Migrations `create_tenant_payout_accounts_table` and `add_payout_account_to_owner_payouts_table` executed and verified.
- Model `TenantPayoutAccount` implemented with `BelongsToTenant`, `SoftDeletes`, and `masked_number`.
- Actions `SaveTenantPayoutAccount`, `DeleteTenantPayoutAccount`, and `MakeDefaultPayoutAccount` enforce limits (max 3), tenant locking, in-flight check, and atomic default switching.
- Mail notification `PayoutAccountChangedNotification` queued and sent on account create/update/delete.
- Controller `OwnerPayoutAccountController` with rate-limiting (`throttle:3,10`) and password confirmation.
- Admin view displays "Rekening baru (< 24 jam)" badge.
- Tests passing: `tests/Feature/Payout/TenantPayoutAccountTest.php` (10 tests), `OwnerPayoutFlowTest.php` (14 tests), `OwnerApplicationActionsTest.php` (11 tests), `SettingsTest.php` (11 tests).
- PHPStan: 0 errors. Pint clean. Architecture rules passing.
