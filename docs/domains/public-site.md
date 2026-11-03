# Public site (landing, pricing, legal, sitemap)

BookQu's own marketing pages, separate from tenant storefronts.

## Entry points

| Route | Code |
|---|---|
| `/` | closure → `welcome.blade.php` (components in `resources/views/components/landing/`: hero, features, pricing, cta, navbar, footer) |
| `privacy-policy` `/kebijakan-privasi` (redirect from `/privacy-policy`) | closure → `privacy-policy.blade.php` |
| `terms-conditions` `/syarat-ketentuan` (redirect from `/terms-conditions`) | closure → `terms-conditions.blade.php` |
| `sitemap` `/sitemap.xml` | `SitemapController` |
| `/up` | Laravel health route |

The storefront slug pattern excludes these paths (`routes/web.php`).

## Evidence

`tests/Feature/LandingPagePricingTest.php`, `PrivacyPolicyTest.php`, `TermsConditionsTest.php`, `SitemapTest.php`.

## Status

Functional: Implemented · Verification: Verified · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| `components/landing/pricing.blade.php` queries `Tenant` directly | BQ-034 |
| Requirement FR-PUBLIC-005 (landing & pricing) is `Proposed` | — |
| Audit P0/P1: redirect loop on "Masuk ke Dashboard"; no mobile navigation; reverse tabnabbing on external links | BQ-041, BQ-061 |
