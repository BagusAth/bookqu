# Platform balance & payouts

Under the platform escrow model, customer payments are collected with BookQu's own gateway credentials; the tenant's share is meant to accumulate in a platform balance that the owner withdraws as payouts.

## Entry points

| Route | Code |
|---|---|
| `owner.payments` (Pembayaran page, BQ-141: tabs Ringkasan, Transaksi, Saldo & Pencairan, Rekening, Pengaturan); `owner.settings.balance` answers 301 to its Saldo tab | `Owner/OwnerBalanceController` → `Actions/Owner/GetPaymentsPage`, `GetBalanceLedger` |
| `owner.payouts.proof` (own tenant only) | `OwnerBalanceController::proof` |
| `owner.payout-accounts.*` (CRUD saved payout accounts) | `Owner/OwnerPayoutAccountController` → `Actions/Owner/*TenantPayoutAccount` |
| `admin.payouts*` (`role:admin`) | `Admin/AdminPayoutController` → `Actions/Admin/ProcessOwnerPayout` |
| `owner.payouts.request` | `Owner/OwnerBalanceController::store` → `Actions/Owner/RequestOwnerPayout` |

`RequestOwnerPayout` locks the tenant row, verifies account standing and withdrawal limits via `Domain/Payout/PayoutEligibility` (email verification, complete business profile, platform payment mode, positive balance, `max_open`, saved accounts, and daily caps), holds (debits) the amount and creates an `owner_payouts` row with the chosen saved bank account snapshot in one transaction. `TenantBalanceLedger` credits a booking payment's full amount on provider success (fee 0%, idempotent through `payments.balance_credited_at`, inside the lifecycle's tenant-locked transaction) and debits a refund of a credited payment. `ProcessOwnerPayout` applies `Domain/Payout/PayoutState` transitions under the tenant lock; `rejected` and `failed` return the amount; owners get a database notification.

The minimum payout is `payouts.min_amount` (request rule, `RequestOwnerPayout` and the balance form). Adding, editing and deleting a payout account share `throttle:3,10,payout-account-changes`; switching the primary account has its own `throttle:20,10,payout-account-default` (BQ-127).

## Data

`tenants.saldo_platform`; `tenant_payout_accounts`: `idtenant`, `bank_kode`, `bank_nama`, `nomor_rekening`, `nama_pemilik_rekening`, `is_default`, `changed_at` (at most `payouts.max_accounts` live accounts per tenant, default 3, BQ-123; the unique `idtenant`+`bank_kode`+`nomor_rekening` index includes soft-deleted rows, so re-adding a deleted account restores its row and a live duplicate is refused, BQ-127); `payments.balance_credited_at`; `owner_payouts`: `idtenant`, `request_key` (UUID), `payout_account_id`, `bank_kode`, amount, snapshot bank account, `status` (`pending`, `approved`, `success`, `failed`, `rejected`), `approved_at`, `approved_by` (foreign key to `users.id`, `nullOnDelete`, BQ-127), `processed_at`, `processed_by` (no foreign key yet), `rejection_reason`, `transfer_reference`, `proof_path` (private `local` disk). `DeleteOwnerAccount` refuses account deletion whenever `saldo_platform != 0` or when in-flight payouts (`pending`, `approved`) exist (BQ-122).

BQ-141: the balance ledger has no table. `GetBalanceLedger` reads the records whose writes change `saldo_platform`:

- credited booking payments (`balance_credited_at`), as money in;
- refunds of credited payments, as money out;
- payout holds at request, as money out;
- rejected or failed payouts at processing, as money back in.

Its total therefore equals the balance (AC-PAY-02). A balance written directly, as the local `DatabaseSeeder` does, shows up as a difference. The page is Pemilik only through `payouts.manage` (AC-PAY-01) and stays open when the subscription has ended (D-002). The owner gateway form is removed (D-016, AC-PAY-05). Evidence: `Owner/PembayaranPageTest`.

## Evidence

`tests/Feature/Payout/OwnerPayoutFlowTest.php`, `tests/Feature/Payout/TenantPayoutAccountTest.php`, `tests/Feature/Payout/PayoutEligibilityTest.php`, `tests/Feature/Payout/PayoutDialogAndNotificationTest.php`, `tests/Feature/Actions/OwnerApplicationActionsTest.php`, `tests/Feature/Owner/SettingsTest.php`, `tests/Feature/Owner/DeleteAccountWithOpenFundsTest.php`.

## Status

Functional: Implemented (BQ-015, BQ-103, BQ-122, BQ-123, BQ-124, BQ-125) · Verification: Verified (`OwnerPayoutFlowTest`, `TenantPayoutAccountTest`, `PayoutEligibilityTest`, `PayoutDialogAndNotificationTest`) · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| Transfers are manual (no disbursement API); the admin records the reference and proof | — (v1 decision) |
| `owner_payouts.processed_by` has no foreign key to `users.id` (found during BQ-127) | — (not yet an item) |
| A refund can drive the balance below zero when the owner has already withdrawn; the admin settles it outside the app | — (v1 decision) |

BQ-138: an accepted partial refund debits the recorded refund amount. Admin failure and retry change transfer status and leave the existing debit in place.
