# Tenancy & business profile

Owns tenant isolation, tenant resolution (slug and custom domain), and the business profile used by the storefront.

## Entry points

| Kind | Where |
|---|---|
| Middleware `tenant` | `app/Http/Middleware/TenantMiddleware.php` — owner routes: tenant of the logged-in owner; public routes: `{slug_usaha}` or the request host as custom domain, only while its DNS check is `connected` (BQ-154) |
| Middleware `owner.profile` | `app/Http/Middleware/EnsureOwnerProfileComplete.php` — requires `namabisnis`, `slug`, `jenisbisnis`, `nomorhp`; also checks direct-gateway keys when `payment_mode = owner` |
| Routes | `owner.settings*`, `owner.profile.complete`, `owner.settings.profile` (`routes/web.php`); public `{slug_usaha}` and `Route::domain('{custom_domain}')` groups |
| Controllers | `Owner/OwnerSettingController` |

## Operations

`Actions/Tenant/CreateInitialProfile`, `UpdateBusinessProfile` (also uploads logo), `UpdatePaymentSettings` (Tier 3), `UpdateAppearanceSettings` (Tier 3).
Registration derives the slug from the business name and appends a numeric suffix for taken or reserved slugs. `ReservedTenantSlugs` uses registered route prefixes and reserved words. Owner profile creation and updates also refuse reserved slugs.

`GetStorefrontTenant` loads a public-field projection under `tenant:public:v2:{id}` using the tenant id already verified by middleware. Profile, appearance, landing-page and payment-setting writes invalidate it. Missing middleware context fails closed; a slug used as an unregistered hostname is not a custom-domain mapping.

## Mechanism

- `App\Support\TenantContext` holds the current tenant ID. Request middleware establishes and clears it. Jobs, webhook processing, commands and streamed exports use `run()` with a trusted tenant ID and restore the previous context on return or exception.
- `App\Traits\BelongsToTenant` applies `TenantScope` to tenant-owned models. An empty context admits no rows. Creation fills a missing `idtenant` from the context.
- `App\Traits\ResolvesOwnerTenant::resolveTenant()` — controllers get the owner's tenant and re-check `iduser`.
- `App\Support\CustomerBookingRoutes` — builds storefront URLs for slug vs custom domain.
- `App\Domain\Tenant\CustomDomain` — shares the host rule across route matching, tenant lookup, storefront URLs and domain registration. Platform hosts come from `custom-domains.platform_hosts` and the host in `app.url`. Their subdomains stay on platform routing.
- Models without the scope: `Tenant`, `User`, `Plan`, `Refund`, `BookingLog`, `UsageLog` (reach them through an owned parent).

## Data

`tenants`: `iduser`, `namabisnis`, `slug` (unique), `custom_domain`, `jenisbisnis`, `alamat`, `deskripsi`, `nomorhp`, `logo_path`, `banner_path`, `cancel_before_hours`, `reschedule_before_hours`, `weekend_price_type/value`, `saldo_platform`, appearance fields, direct-gateway fields (`payment_mode`, `midtrans_*`).

The four Midtrans sandbox/production client/server key columns use encrypted casts and nullable TEXT storage; all four are hidden from model array/JSON serialization. Gateway code reads decrypted attributes explicitly. The BQ-004 migration handles legacy backfill and reversible decryption; rollback order and APP_KEY retention are mandatory in `docs/operations.md` §3/§6.

## Invariants

- **INV-TEN-01** — no cross-tenant read or write; `TenantScope` fails closed.
- **INV-TEN-02** — request tenant context comes from verified middleware resolution. Background work establishes context from a trusted stored subject or queued tenant ID.

## Evidence

`tests/Feature/P0SecurityTest.php`, `RF09StabilityAndHardeningTest.php`, `Customer/CustomDomainBookingTest.php`, `Owner/SettingsTest.php`, `Actions/OwnerApplicationActionsTest.php`.

BQ-004: `Tenancy/TenantGatewayCredentialsTest.php` (storage, serialization, gateway reads, key rotation, public cache), `Tenancy/TenantCredentialMigrationTest.php` (MySQL backfill/down/cache purge), and `Ops/TenantCredentialRollbackTest.php` (automatic rollback ordering and fail-closed behavior).

## Status

Functional: Implemented · Verification: Needs Verification · Architecture: Target, with gaps below.

## Known gaps

| Gap | Item |
|---|---|
| Owner views write `/owner/...` URLs by hand; storefront links bypass `CustomerBookingRoutes` | BQ-128 |
| Renaming the business changes the slug and breaks old links (no redirect); old slug cache not cleared | BQ-144 |
| TLS certificates for custom domains are not issued by the application; the server needs on-demand certificates before the feature is released | Tech Lead (ops) |

BQ-009 limits explicit scope bypasses to platform-admin reads, tenant discovery, cross-tenant candidate scans before the runner, and customer-access resolution. The architecture test checks the reviewed files and call counts. Payment route binding and normal tenant-owned relationships keep their scopes. See [ADR-0010](../adr/ADR-0010-authorization-and-tenant-scoping.md) and the [BQ-009 inventory](../work/done/BQ-009-audit-scope-bypasses.md#inventory).

BQ-131: accepted, non-revoked `tenant_members` are resolved fresh for each owner request; ambiguous memberships fail closed, and a pemilik must equal tenants.iduser. Migration backfills existing owners; new tenant creation establishes the membership. Resource policies check tenant plus TenantPermission ability; foreign bound records remain 404. Revocation and per-route ability mapping are covered by `Team/TenantPermissionTest`; MySQL backfill by `TenantMembershipBackfillTest`.

BQ-146: outstanding invitations reserve a non-owner seat until revoked, accepted or expired. Issue and acceptance both use the tenant lock and EntitlementRules. Owners may revoke access and edit non-owner roles even after a downgrade. Booking history records actor ID and role from the authenticated membership. The owner activity list is tenant-filtered and stores event and subject references without request payloads. Staff writes and service links are retired while the historical tables are retained for one release.

BQ-144: authorised business managers can update the tenant booking policy, including refund eligibility, cancellation/reschedule deadlines and booking-window settings. Policy writes share the tenant lock with booking claims and invalidate the public tenant cache after commit.
