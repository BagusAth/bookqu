# Staged modules (Tier 2 and Tier 3)

Modules whose backend exists but which are not fully released. The release policy is in `docs/product.md` → Release tiers; the rationale is ADR-0002. `tests/Feature/Owner/OwnerSidebarNavigationTest.php` enforces what the sidebar shows.

| Module | Routes | Code | Tier | Server-side state |
|---|---|---|---|---|
| Vouchers | `owner.vouchers*`; storefront `validate-voucher` | `Owner/OwnerVoucherController`, `Models/Voucher`; `BookingController::validateVoucher` returns HTTP 422 | 3 (hidden) | Owner CRUD reachable by URL |
| Additional items | `owner.additional-items*` | `Owner/OwnerAdditionalItemController`, `Models/AdditionalItem` | 3 | Owner CRUD reachable by URL |
| Staff & resources | `owner.staff*`, `owner.resources*`, `owner.staff-resources` | `Owner/OwnerStaffResourceController` (292 lines), `Models/Staff`, `Models/Resource` | 3 | Reachable; links also affect `Service::hasActiveFulfillment()` |
| Appearance | `owner.settings.appearance*` | `Owner/OwnerAppearanceController`, `Actions/Tenant/UpdateAppearanceSettings` | 3 | Reachable; storefront ignores the settings |
| Direct payment gateway | none since BQ-141 (D-016: the owner gateway form and its routes are removed) | Backend left: `OwnerSettingController::updatePaymentSettings` (unrouted), `Actions/Tenant/UpdatePaymentSettings`, `tenants.payment_mode` and the Midtrans key columns | 3 | No owner route; `payment_mode = owner` still changes checkout behaviour for tenants that already have it |
| Media assets | `owner.settings.assets*` | `Owner/OwnerAssetController`, `Models/Asset` | 3 | Reachable; images only |
| Landing page builder | `owner.landing-page*` (`subscription:pro`) | `Owner/OwnerLandingPageController` | 3 | Reachable for Pro/trial |
| Integrations | `owner.settings.integrations` | `Owner/OwnerIntegrationController` | 2 ("Coming soon") | Display only |
| Analytics, schedule report | see `insights.md` | | 2 (locked) | Reachable |
| Platform balance | see `balance-payout.md` | | 1 (released by BQ-015) | Released: withdraw request and admin review |

Since BQ-008 (2026-11-01) the "Server-side state" column describes the code behind the gate: `module:<name>` (`config/modules.php`) answers 404 for every Tier 3 route and refuses Tier 2 writes with 403, unless an environment releases the module.

## Status

Functional: Implemented, gated in the UI only · Verification: Needs Verification · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Direct-gateway keys stored unencrypted | BQ-004 |
| Audit P0: staff/resource relations never detached when unchecked | BQ-045 |
| Staff, resource and add-on UI still shown in released owner pages (services, service modals, bookings) | BQ-157 |

Target decision: [ADR-0011](../adr/ADR-0011-server-side-release-gating.md) — module registry `config/modules.php` with `module:<name>` middleware (`hidden` → 404, `locked` → lock page only), implemented by BQ-008 (2026-11-01): Tier 3 routes answer 404, Tier 2 routes refuse writes with 403.

BQ-130: ADR-0021 registry entries are hidden for unbuilt redesign capabilities, including the old integrations/analytics/report entry points by default. Core completed capabilities stay released; no unbuilt capability is promoted. `ModuleRelease` normalizes unknown states to hidden. Routes apply module, feature and permission gates in that order; legacy gate override tests explicitly enable a module to exercise its inner behavior. Sidebar/navigation comes from `config/owner-navigation.php`, prepared by `GetOwnerNavigation`; only working, role-allowed entries render, with plan upgrade locks (Unit hidden below Pro).

BQ-128: `Actions/Owner/GetOwnerShell` prepares the whole owner shell once per request from that definition (view composer on `layouts.owner-layout`): the sidebar groups, the phone bottom bar (Beranda, Kalender, + Booking, Booking, Lainnya) and its "Lainnya" sheet, one active menu by route name (`match` prefixes in `config/owner-navigation.php`, longest wins), menu badges (bookings needing a decision, the slot runway warning, unanswered reviews, trial days left), the account menu and the storefront link (`CustomerBookingRoutes`). A locked menu opens the shared upgrade prompt. The top bar's search (`owner.search`, tenant-scoped) and "+ Booking" (walk-in form with `owner.bookings.walkin-slots`, hidden when the subscription has ended) are FR-SHELL-002. Evidence: `Owner/OwnerAppShellTest`, `Owner/OwnerSidebarNavigationTest`.
