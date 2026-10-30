# Staged modules (Tier 2 and Tier 3)

Modules whose backend exists but which are not fully released. The release policy is in `docs/product.md` → Release tiers; the rationale is ADR-0002. `tests/Feature/Owner/OwnerSidebarNavigationTest.php` enforces what the sidebar shows.

| Module | Routes | Code | Tier | Server-side state |
|---|---|---|---|---|
| Vouchers | `owner.vouchers*`; storefront `validate-voucher` | `Owner/OwnerVoucherController`, `Models/Voucher`; `BookingController::validateVoucher` returns HTTP 422 | 3 (hidden) | Owner CRUD reachable by URL |
| Additional items | `owner.additional-items*` | `Owner/OwnerAdditionalItemController`, `Models/AdditionalItem` | 3 | Owner CRUD reachable by URL |
| Staff & resources | `owner.staff*`, `owner.resources*`, `owner.staff-resources` | `Owner/OwnerStaffResourceController` (292 lines), `Models/Staff`, `Models/Resource` | 3 | Reachable; links also affect `Service::hasActiveFulfillment()` |
| Appearance | `owner.settings.appearance*` | `Owner/OwnerAppearanceController`, `Actions/Tenant/UpdateAppearanceSettings` | 3 | Reachable; storefront ignores the settings |
| Direct payment gateway | `owner.settings.payment` (POST), `owner.settings.payments`, `owner.settings.payment-setting` | `OwnerSettingController::updatePaymentSettings`, `Actions/Tenant/UpdatePaymentSettings`, `Owner/OwnerPaymentSettingsController` | 3 | Reachable; saves Midtrans keys in plain text; `payment_mode = owner` changes checkout behaviour |
| Media assets | `owner.settings.assets*` | `Owner/OwnerAssetController`, `Models/Asset` | 3 | Reachable; images only |
| Landing page builder | `owner.landing-page*` (`subscription:pro`) | `Owner/OwnerLandingPageController` | 3 | Reachable for Pro/trial |
| Integrations | `owner.settings.integrations` | `Owner/OwnerIntegrationController` | 2 ("Coming soon") | Display only |
| Analytics, schedule report | see `insights.md` | | 2 (locked) | Reachable |
| Platform balance | see `balance-payout.md` | | 2 | Payout request reachable |

## Status

Functional: Implemented, gated in the UI only · Verification: Needs Verification · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Hiding a link is not authorization: every Tier 3 endpoint above still works by URL | BQ-008 |
| Direct-gateway keys stored unencrypted | BQ-004 |
| Audit P0: staff/resource relations never detached when unchecked | BQ-045 |

Target decision: [ADR-0011](../adr/ADR-0011-server-side-release-gating.md) — module registry `config/modules.php` with `module:<name>` middleware (`hidden` → 404, `locked` → lock page only), implemented by BQ-008. Not implemented yet.
