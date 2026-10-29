# Tenancy & business profile

Owns tenant isolation, tenant resolution (slug and custom domain), and the business profile used by the storefront.

## Entry points

| Kind | Where |
|---|---|
| Middleware `tenant` | `app/Http/Middleware/TenantMiddleware.php` — owner routes: tenant of the logged-in owner; public routes: `{slug_usaha}` or the request host as custom domain |
| Middleware `owner.profile` | `app/Http/Middleware/EnsureOwnerProfileComplete.php` — requires `namabisnis`, `slug`, `jenisbisnis`, `nomorhp`; also checks direct-gateway keys when `payment_mode = owner` |
| Routes | `owner.settings*`, `owner.profile.complete`, `owner.settings.profile` (`routes/web.php`); public `{slug_usaha}` and `Route::domain('{custom_domain}')` groups |
| Controllers | `Owner/OwnerSettingController` |

## Operations

`Actions/Tenant/CreateInitialProfile`, `UpdateBusinessProfile` (also uploads logo), `UpdatePaymentSettings` (Tier 3), `UpdateAppearanceSettings` (Tier 3).
Tenant creation happens at registration (`Auth/RegisteredUserController::store`, slug = `Str::slug(business name)` with numeric suffix on collision).

## Mechanism

- `App\Support\TenantContext` — request-scoped holder of the current tenant id (registered `scoped` in `AppServiceProvider`), cleared after each request.
- `App\Models\Scopes\TenantScope` + `App\Traits\BelongsToTenant` — global scope on 15 tenant-owned models (AdditionalItem, Asset, Booking, Category, CustomerNote, OwnerBlockedDate, OwnerPayout, Payment, Resource, Review, Schedule, Service, Staff, Subscription, Voucher). Empty context ⇒ `WHERE 1 = 0` (fail closed). `creating` fills `idtenant` from context.
- `App\Traits\ResolvesOwnerTenant::resolveTenant()` — controllers get the owner's tenant and re-check `iduser`.
- `App\Support\CustomerBookingRoutes` — builds storefront URLs for slug vs custom domain.
- Models without the scope: `Tenant`, `User`, `Plan`, `Refund`, `BookingLog`, `UsageLog` (reach them through an owned parent).

## Data

`tenants`: `iduser`, `namabisnis`, `slug` (unique), `custom_domain`, `jenisbisnis`, `alamat`, `deskripsi`, `nomorhp`, `logo_path`, `banner_path`, `cancel_before_hours`, `reschedule_before_hours`, `weekend_price_type/value`, `saldo_platform`, appearance fields, direct-gateway fields (`payment_mode`, `midtrans_*`).

## Invariants

- **INV-TEN-01** — no cross-tenant read or write; `TenantScope` fails closed.
- **INV-TEN-02** — tenant context is set by `TenantMiddleware`, never from request input.

## Evidence

`tests/Feature/P0SecurityTest.php`, `RF09StabilityAndHardeningTest.php`, `Customer/CustomDomainBookingTest.php`, `Owner/SettingsTest.php`, `Actions/OwnerApplicationActionsTest.php`.

## Status

Functional: Implemented · Verification: Needs Verification · Architecture: Target, with gaps below.

## Known gaps

| Gap | Item |
|---|---|
| 55 `withoutGlobalScope(s)` calls in 29 files not individually justified | BQ-009 |
| Main-domain detection defined twice (`TenantMiddleware::$isCustomDomain` vs `routes/web.php` exclusion list) | BQ-035 |
| Tenant direct-gateway secrets stored in plain text and cached with the tenant (`tenant:slug:*`) | BQ-004 |
| Slugs can collide with app routes (`login`, `owner`, `manage`, `admin`, …) | BQ-011 |
| Custom-domain storefront behaviour only partly tested | BQ-039 |
