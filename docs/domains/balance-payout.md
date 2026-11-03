# Platform balance & payouts

Under the platform escrow model, customer payments are collected with BookQu's own gateway credentials; the tenant's share is meant to accumulate in a platform balance that the owner withdraws as payouts.

## Entry points

| Route | Code |
|---|---|
| `owner.settings.balance` (released by BQ-015: balance, withdraw form, history) | `Owner/OwnerBalanceController` |
| `owner.payouts.proof` (own tenant only) | `OwnerBalanceController::proof` |
| `owner.payout-accounts.*` (CRUD saved payout accounts) | `Owner/OwnerPayoutAccountController` → `Actions/Owner/*TenantPayoutAccount` |
| `admin.payouts*` (`role:admin`) | `Admin/AdminPayoutController` → `Actions/Admin/ProcessOwnerPayout` |
| `owner.payouts.request` | `Owner/OwnerBalanceController::store` → `Actions/Owner/RequestOwnerPayout` |

`RequestOwnerPayout` locks the tenant row, verifies account standing and withdrawal limits via `Domain/Payout/PayoutEligibility` (email verification, complete business profile, platform payment mode, positive balance, `max_open`, saved accounts, and daily caps), holds (debits) the amount and creates an `owner_payouts` row with the chosen saved bank account snapshot in one transaction. `TenantBalanceLedger` credits a booking payment's full amount on provider success (fee 0%, idempotent through `payments.balance_credited_at`, inside the lifecycle's tenant-locked transaction) and debits a refund of a credited payment. `ProcessOwnerPayout` applies `Domain/Payout/PayoutState` transitions under the tenant lock; `rejected` and `failed` return the amount; owners get a database notification.

## Data

`tenants.saldo_platform`; `tenant_payout_accounts`: `idtenant`, `bank_kode`, `bank_nama`, `nomor_rekening`, `nama_pemilik_rekening`, `is_default`, `changed_at` (max 3 accounts per tenant, BQ-123); `payments.balance_credited_at`; `owner_payouts`: `idtenant`, `request_key` (UUID), `payout_account_id`, `bank_kode`, amount, snapshot bank account, `status` (`pending`, `approved`, `success`, `failed`, `rejected`), `rejection_reason`, `transfer_reference`, `proof_path` (private `local` disk), `processed_by`. `DeleteOwnerAccount` refuses account deletion whenever `saldo_platform != 0` or when in-flight payouts (`pending`, `approved`) exist (BQ-122).

## Evidence

`tests/Feature/Payout/OwnerPayoutFlowTest.php`, `tests/Feature/Payout/TenantPayoutAccountTest.php`, `tests/Feature/Payout/PayoutEligibilityTest.php`, `tests/Feature/Actions/OwnerApplicationActionsTest.php`, `tests/Feature/Owner/SettingsTest.php`, `tests/Feature/Owner/DeleteAccountWithOpenFundsTest.php`.

## Status

Functional: Implemented (BQ-015, BQ-103, BQ-122, BQ-123, BQ-124) · Verification: Verified (`OwnerPayoutFlowTest`, `TenantPayoutAccountTest`, `PayoutEligibilityTest`) · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| Transfers are manual (no disbursement API); the admin records the reference and proof | — (v1 decision) |
| A refund can drive the balance below zero when the owner has already withdrawn; the admin settles it outside the app | — (v1 decision) |
