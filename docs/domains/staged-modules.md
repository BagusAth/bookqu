# Staged modules (Tier 2 and Tier 3)

Modules whose backend exists but which are not fully released. The release policy is in `docs/product.md` → Release tiers; the rationale is ADR-0002. `tests/Feature/Owner/OwnerSidebarNavigationTest.php` enforces what the sidebar shows.

| Module | Routes | Code | Tier | Server-side state |
|---|---|---|---|---|
| Vouchers | `owner.vouchers*`; storefront `validate-voucher` | `Owner/OwnerVoucherController`, `Models/Voucher`; `BookingController::validateVoucher` returns HTTP 422 | 3 (hidden) | Owner CRUD reachable by URL |
| Additional items | `owner.additional-items*` | `Owner/OwnerAdditionalItemController`, `Models/AdditionalItem` | 3 | Owner CRUD reachable by URL |
| Halaman Booking (BQ-142, replaces Appearance and Media assets) | `owner.booking-page*`; `owner.settings.appearance` and `owner.settings.assets` answer 301 into it | `Owner/OwnerBookingPageController`, `Actions/Tenant/GetBookingPageData`, `UpdateAppearanceSettings`, `BuildBookingQr`, `Actions/Media/*` | redesign module `booking-page` (hidden until the Tech Lead releases it, ADR-0021) | Built; the storefront applies the look through `Support/StorefrontBranding` |
| Landing page and own domain | `owner.booking-page.landing*`, `owner.booking-page.domain*` (`subscription:landing-page`); `owner.landing-page` redirects | `Owner/OwnerBookingPageController` (BQ-154) | module `booking-page` | Pro only |
| Integrations | `owner.settings.integrations` | `Owner/OwnerIntegrationController` | 2 ("Coming soon") | Display only |
| Analytics, schedule report | see `insights.md` | | 2 (locked) | Reachable |
| Platform balance | see `balance-payout.md` | | 1 (released by BQ-015) | Released: withdraw request and admin review |

Since BQ-008 (2026-11-01) the "Server-side state" column describes the code behind the gate: `module:<name>` (`config/modules.php`) answers 404 for every Tier 3 route and refuses Tier 2 writes with 403, unless an environment releases the module.


BQ-157 (2026-11-16): the withdrawn modules are removed. Staff and resources (models, policies, owner routes, `staff`, `service_staff`, `resources` and `service_resource` tables; resource rows became units, ADR-0017) and the direct payment gateway (`payment_mode = owner`, `UpdatePaymentSettings`, tenant keys in `MidtransPaymentGateway`; a migration erases the stored `midtrans_*` keys and puts every tenant on `platform`) are gone. `Service::hasActiveFulfillment()` no longer gates services. Unused keys left `config/modules.php`. Old paths answer per `owner-portal.md` §10.2: `/owner/staff-resources` → Tim, `/owner/settings/payments` and `/owner/settings/payment-setting` → `/owner/payments`; `/owner/resources` and `/owner/staff*` answer 404.

## Status

Functional: Implemented, gated in the UI only · Verification: Needs Verification · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|

Target decision: [ADR-0011](../adr/ADR-0011-server-side-release-gating.md) — module registry `config/modules.php` with `module:<name>` middleware (`hidden` → 404, `locked` → lock page only), implemented by BQ-008 (2026-11-01): Tier 3 routes answer 404, Tier 2 routes refuse writes with 403.

BQ-130: ADR-0021 registry entries are hidden for unbuilt redesign capabilities, including the old integrations/analytics/report entry points by default. Core completed capabilities stay released; no unbuilt capability is promoted. `ModuleRelease` normalizes unknown states to hidden. Routes apply module, feature and permission gates in that order; legacy gate override tests explicitly enable a module to exercise its inner behavior. Sidebar/navigation comes from `config/owner-navigation.php`, prepared by `GetOwnerNavigation`; only working, role-allowed entries render, with plan upgrade locks (Unit hidden below Pro).

BQ-128: `Actions/Owner/GetOwnerShell` prepares the whole owner shell once per request from that definition (view composer on `layouts.owner-layout`): the sidebar groups, the phone bottom bar (Beranda, Kalender, + Booking, Booking, Lainnya) and its "Lainnya" sheet, one active menu by route name (`match` prefixes in `config/owner-navigation.php`, longest wins), menu badges (bookings needing a decision, the slot runway warning, unanswered reviews, trial days left), the account menu and the storefront link (`CustomerBookingRoutes`). A locked menu opens the shared upgrade prompt. The top bar's search (`owner.search`, tenant-scoped) and "+ Booking" (walk-in form with `owner.bookings.walkin-slots`, hidden when the subscription has ended) are FR-SHELL-002. Evidence: `Owner/OwnerAppShellTest`, `Owner/OwnerSidebarNavigationTest`.
