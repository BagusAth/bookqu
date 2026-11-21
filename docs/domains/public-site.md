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

## Tenant booking page look and sharing (BQ-142)

The owner sets the look of the storefront in Halaman Booking (`owner.booking-page`, owner-portal.md §8.9, D-009):

- logo and cover from the media gallery;
- theme colour, button style (pill, rounded, square), card style (elevated, outline, soft), font and description.

`App\Support\StorefrontBranding` holds the allowed values and normalises stored ones for `customer/layouts/booking-shell`, which writes them as `--sf-*` variables for every customer page (BQ-165). Unknown values fall back to the defaults, so nothing reaches the style block unvalidated. Older values map to the new names. It also derives the text colours on the brand colour, the brand text colour and tints (every text pair at 4.5:1 or more), the corner radii per button style and the shadows per card style.

Templates (D-028): Modern, Rapi and Tegas fill button style, card style and font in Tampilan; the brand colour stays the business's. `StorefrontBranding::templateFor()` recognises the current template from the three saved values, otherwise `custom` ("Kustom"); nothing new is stored.

Sharing (FR-PUBLIC-006, D-010):

- copy, open and WhatsApp share;
- an SVG QR code of the current link (`Actions/Tenant/BuildBookingQr`, `bacon/bacon-qr-code`, approved 2026-11-10);
- a printable poster;
- ready texts for an Instagram bio and a WhatsApp message.

Every image lives in the tenant's media gallery under `tenants/{id}/assets` (FR-ASSET-005):

- `Actions/Media/StoreMediaAsset` adds images;
- `Actions/Media/DeleteMediaAsset` asks for confirmation while an image is the logo, the cover, a service image or the landing page banner, then clears those references.

Evidence: `Owner/HalamanBookingPageTest`.

## Landing page and own domain for Pro (BQ-154)

Two Halaman Booking sections for Pro (owner-portal.md §8.9, FR-PUBLIC-007, D-005). Below Pro they show the upgrade prompt, and their routes refuse writes through `subscription:landing-page` (AC-HBK-03).

- **Landing page:** one row per business in `tenant_landing_pages` (template Modern Business, Creative Studio or Minimalist Clinic; headline; sections Tentang kami, Layanan unggulan, Lokasi & kontak; banner from the media gallery; `published_at`). `Actions/Tenant/SaveLandingPage` saves, publishes or hides it; `owner.booking-page.landing.preview` shows the saved draft. Once published and while the business stays on Pro, `GetLandingPage` puts it at the top of the storefront home (`customer/booking/partials/landing`), above the booking flow. Section content comes from the business profile and its active services, escaped.
- **Own domain:** stored normalised (`App\Domain\Tenant\CustomDomain`: lower case, no scheme, path or trailing dot, never a platform domain) and unique across businesses (AC-HBK-04). Saving sets `custom_domain_status = pending` and queues `CheckCustomDomainJob`. `Actions/Tenant/CheckCustomDomain` asks DNS through `App\Infrastructure\Dns\DnsLookup`: connected only when (1) the CNAME is `CUSTOM_DOMAIN_CNAME_TARGET` or an A record is in `CUSTOM_DOMAIN_A_RECORDS` (`config/custom-domains.php`, set per environment), (2) the TXT record at `_bookqu.<domain>` is `bookqu-verify=<custom_domain_token>`, which proves the business owns the domain (a new domain gets a new token), and (3) the business still has the Pro capability; a business below Pro keeps its domain saved but it stops resolving. "Cek sekarang" runs it at once and shows the records found; `domains:check` re-checks every domain hourly.

Evidence: `Owner/LandingPageAndDomainTest`, `Customer/CustomDomainBookingTest`.

## Customer pages design (BQ-165)

Every customer page — storefront, schedule, checkout, waiting for payment, success and invoice, payment status, manage, reschedule, access link and errors — uses design A "Sampul Imersif" ([spec](../design/booking-pelanggan/desain-a-sampul-imersif.md)) and follows the owner's appearance: colour, font, button style and card style. Phone, tablet and desktop have their own layouts. Views only display server data; availability, prices, payment status and access stay with the existing Actions. The service list is server-rendered and services can be chosen without JavaScript; the customer Vite bundle and the page scripts add search, filters, the calendar and the time picker. The checkout refreshes the server quote after changes. Saved contact details are scoped to the business on the device. BQ-164 brought the pages onto one shell first; BQ-165 replaced its look.

Evidence: `Unit/StorefrontBrandingTest`, `Components/CustomerComponentsTest`, `Owner/HalamanBookingPageTest`, `Customer/CustomerFrontendSpecificationTest`, `Customer/ThreeStepBookingFlowTest`, `Customer/BookingManageTest`.

## Evidence

`tests/Feature/LandingPagePricingTest.php`, `PrivacyPolicyTest.php`, `TermsConditionsTest.php`, `SitemapTest.php`.

## Status

Functional: Implemented · Verification: Verified · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|

| Requirement FR-PUBLIC-005 (landing & pricing) is `Proposed` | — |
| Audit P0/P1: redirect loop on "Masuk ke Dashboard"; no mobile navigation; reverse tabnabbing on external links | BQ-041, BQ-061 |

BQ-130: landing pricing is prepared by `GetPublicPlanCards`; the Blade contains no model queries or tenant selection from session input. Prices match the plans table used at checkout, and feature labels share the owner comparison with unreleased capabilities omitted. Google Calendar sync and down-payment claims were removed until supported. Ambiguous memberships fail closed rather than choosing a session tenant.
