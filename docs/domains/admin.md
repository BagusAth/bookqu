# Platform admin

Platform-level oversight for BookQu staff. Separate from owner permissions (FR-ADMIN-004).

## Entry points

| Route | Code |
|---|---|
| `admin.dashboard` (`auth`, `role:admin`) | `Admin/AdminDashboardController::index` — global counts (tenants, owners, subscriptions by status), subscription revenue, latest tenants |

The controller reads across tenants with `withoutGlobalScopes()` (7 calls); this is intended for an admin-only route.

## Evidence

`tests/Feature/Admin/AdminDashboardTest.php`.

## Status

Functional: Partial (read-only dashboard; no tenant management, payout approval or refund processing screens) · Verification: Verified (partial) · Architecture: Not Evaluated

## Known gaps

| Gap | Item |
|---|---|
| No admin workflow for payouts (`owner_payouts.status`) or refunds (`refunds.status`), so both stay `pending` | BQ-015, BQ-003, BQ-038 |
| Admin scope is not defined in requirements beyond FR-ADMIN-001…004 | BQ-038 |
