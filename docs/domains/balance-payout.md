# Platform balance & payouts

Under the platform escrow model, customer payments are collected with BookQu's own gateway credentials; the tenant's share is meant to accumulate in a platform balance that the owner withdraws as payouts.

## Entry points

| Route | Code |
|---|---|
| `owner.settings.balance` (released by BQ-015: balance, withdraw form, history) | `Owner/OwnerBalanceController` |
| `owner.payouts.proof` (own tenant only) | `OwnerBalanceController::proof` |
| `admin.payouts*` (`role:admin`) | `Admin/AdminPayoutController` → `Actions/Admin/ProcessOwnerPayout` |
| `owner.payouts.request` | `Owner/OwnerSettingController::requestPayout` → `Actions/Owner/RequestOwnerPayout` |

`RequestOwnerPayout` locks the tenant row, checks `saldo_platform`, holds (debits) the amount and creates an `owner_payouts` row with the bank account in one transaction. `TenantBalanceLedger` credits a booking payment's full amount on provider success (fee 0%, idempotent through `payments.balance_credited_at`, inside the lifecycle's tenant-locked transaction) and debits a refund of a credited payment. `ProcessOwnerPayout` applies `Domain/Payout/PayoutState` transitions under the tenant lock; `rejected` and `failed` return the amount; owners get a database notification.

## Data

`tenants.saldo_platform`; `payments.balance_credited_at`; `owner_payouts`: `idtenant`, `request_key` (UUID), amount, bank account, `status` (`pending`, `approved`, `success`, `failed`, `rejected`), `rejection_reason`, `transfer_reference`, `proof_path` (private `local` disk), `processed_by`. `DeleteOwnerAccount` refuses account deletion whenever `saldo_platform != 0` or when in-flight payouts (`pending`, `approved`) exist (BQ-122).

## Evidence

`tests/Feature/Payout/OwnerPayoutFlowTest.php`, `tests/Feature/Actions/OwnerApplicationActionsTest.php`, `tests/Feature/Owner/SettingsTest.php`, `tests/Feature/Owner/DeleteAccountWithOpenFundsTest.php`.

## Status

Functional: Implemented (BQ-015, BQ-103, BQ-122) · Verification: Verified (`OwnerPayoutFlowTest`) · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| Transfers are manual (no disbursement API); the admin records the reference and proof | — (v1 decision) |
| A refund can drive the balance below zero when the owner has already withdrawn; the admin settles it outside the app | — (v1 decision) |
