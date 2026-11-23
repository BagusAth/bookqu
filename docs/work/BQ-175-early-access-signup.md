---
id: BQ-175
title: Early access landing page, sign-up storage and admin list
type: feature
status: in-progress
priority: P1
phase: 5
domain: public-site
requirements: [FR-PUBLIC-009, FR-ADMIN-006]
invariants: []
lane: heavy
approval: tech-lead
decision: D-030
launch-blocker: false
depends-on: []
---

# BQ-175: Early access landing page, sign-up storage and admin list

## Problem

BookQu wants businesses to sign up for early access from a dedicated, indexable page (`https://bookqu.my.id/early-access`, design provided by the Product Lead on 2026-11-23). Nothing stores such sign-ups today, and the admin cannot see them. Product decision D-030; the new table and public route need Tech Lead approval (heavy lane: schema and personal data).

## Plan

1. Table `early_access_signups` (platform data, no tenant): `full_name`, `business_name`, `business_type`, `email` (unique, lowercase), `whatsapp_number` (normalized `08…`), timestamps. Model `EarlyAccessSignup` with a factory.
2. `StoreEarlyAccessSignupRequest` validates every field with Indonesian messages; honeypot field `website` must be empty; route throttled at 5 per minute per IP.
3. `CreateEarlyAccessSignup` action stores the sign-up; `EarlyAccessController` shows the page and redirects back to `#daftar` with a success message.
4. Routes `early-access` / `early-access.store` before `/{slug_usaha}`; the slug becomes reserved through `ReservedTenantSlugs`. URL added to `sitemap.xml`.
5. Page built from `x-landing.layout` (new optional `description`, `canonical`, `ogImage` props and a `head` stack), semantic sections with `aria-labelledby`, one `h1`, Open Graph, JSON-LD `WebPage` and `FAQPage`. Reusable cards in `resources/views/components/early-access/`.
6. Admin: `admin.early-access` list (search, 25 per page, WhatsApp link), navigation item, dashboard figure with the last 7 days.
7. Scroll animations with `aos@2.3.4` (new npm dependency, approved by the Tech Lead on 2026-11-23, confirmed through the developer): `data-aos` on every section element and on each form field separately, `once: false` so animations replay; disabled for reduced motion and visible without JavaScript (`js-aos`). The sign-up confirmation is a centered modal `<dialog>`.

## Invariants at risk

None of the booking, payment or tenancy invariants: the table holds no tenant data and the admin route sits behind `role:admin`. The new top-level route would shadow a tenant whose slug is `early-access`: check production before release with `SELECT id FROM tenants WHERE slug = 'early-access';` (must return nothing).

## Tests

- `tests/Feature/EarlyAccess/EarlyAccessSignupTest.php`: page renders with SEO markup; valid sign-up stored and normalized; every validation failure; duplicate email; honeypot; throttle; listed in the sitemap; tenant slug reserved.
- `tests/Feature/Admin/AdminEarlyAccessTest.php`: admin list and search, owner 403, guest redirect; dashboard figures.

## Docs to update

- `docs/product-decisions.md` (D-030), `docs/requirements.md` (FR-PUBLIC-009, FR-ADMIN-006)
- `docs/domains/public-site.md`, `docs/domains/admin.md`

## Evidence (fill when done)
