# Platform balance & payouts

Under the platform escrow model, customer payments are collected with BookQu's own gateway credentials; the tenant's share is meant to accumulate in a platform balance that the owner withdraws as payouts.

## Entry points

| Route | Code |
|---|---|
| `owner.settings.balance` (Tier 2: balance visible, self-service withdrawal on hold) | `Owner/OwnerBalanceController` |
| `owner.payouts.request` | `Owner/OwnerSettingController::requestPayout` → `Actions/Owner/RequestOwnerPayout` |

`RequestOwnerPayout` locks the tenant row, checks `saldo_platform`, debits it and creates an `owner_payouts` row in one transaction; amounts are positive with at most two decimals.

## Data

`tenants.saldo_platform`; `owner_payouts`: `idtenant`, amount, `status` (`pending`, `success`, `rejected`).

## Evidence

`tests/Feature/Actions/OwnerApplicationActionsTest.php`, `Owner/SettingsTest.php`.

## Status

Functional: **Partial** · Verification: Needs Verification · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| Nothing ever credits `saldo_platform` (it is only debited), so the balance stays at its initial value; there is no requirement yet defining fees, settlement timing or who approves payouts | BQ-015 |
| Requirements FR-PAYOUT-001…003 are `Proposed` | BQ-015 |
