---
id: BQ-165
title: Rebuild the customer pages on design A and follow the owner's appearance settings
type: improvement
status: approved
priority: P1
phase: 4
domain: public-site
requirements: [FR-PUBLIC-001, FR-PUBLIC-002, FR-PUBLIC-003, FR-PUBLIC-004, FR-BOOKING-001, FR-BOOKING-002, FR-BOOKING-003, FR-BOOKING-004, FR-BOOKING-008]
invariants: [INV-TEN-01, INV-TOK-01, INV-BOOK-03, INV-SCH-01]
lane: heavy
approval: tech-lead
decision: D-028 (session 7 only, pending)
launch-blocker: true
depends-on: [BQ-164]
---

## Problem

BQ-164 brought every customer page onto one layout and fixed broken paths. The result is correct but visually plain, and the Tech Lead judged it too close to the owner portal. Three things are wrong:

1. **Look.** The pages copy the owner dashboard (owner tokens, BookQu aubergine and ink) instead of looking like the business's own shop.
2. **Owner settings ignored.** Owners choose a button style (pill, rounded, square) and a card style (elevated, outline, soft) in Halaman Booking → Tampilan (owner-portal.md §8.9, FR-PUBLIC-003, D-009). `StorefrontBranding` already returns `buttonRadius`, `cardShadow` and `cardBorder`, but the customer pages apply them only to a few classes.
3. **Screen sizes.** Phone, tablet and desktop share one stretched layout. On a tablet the reschedule summary drops below the content.

The approved design is [`docs/design/booking-pelanggan/desain-a-sampul-imersif.md`](../design/booking-pelanggan/desain-a-sampul-imersif.md) ("the spec"). It is self-contained; section numbers below (§n) point into it.

**Approval.** The Tech Lead approved this work item on 21 Nov 2026. These Tech Lead decisions are part of that approval:

- Design A "Sampul Imersif", revision 3, is the reference for every customer page.
- Customer pages use colours derived from the business colour plus neutrals (spec §2). BookQu ink `#1D1638` and marigold are not used on customer pages.
- Every customer page follows the owner's theme colour, font, button style and card style (spec §3). Card corners follow the button style.
- Title copy becomes "Reservasi di {bisnis}".
- A business banner, when present, becomes the hero background (spec §5).
- The Pro landing page is out of scope.
- Three ready-made templates for the Tampilan menu (spec §3.4) are a product change and wait for [D-028](../product-decisions.md). Only session 7 depends on it.

**Out of scope:**
- the Pro landing page (BQ-154 templates);
- the BookQu marketing site and the owner portal outside Halaman Booking → Tampilan;
- any change to prices, availability, payment, cancellation, refund or access rules;
- new database columns (templates are detected from the three saved values).

## Branch rule

All sessions work on `agathan-bq-165-tampilan-pelanggan`, created on 21 Nov 2026 from `agathan-bq-164-alur-pelanggan` at `af12d1d`.

- Each session is one commit series, done in order.
- Nothing goes to `integration` until the Tech Lead decides.
- Never push to `integration`, `main`, `agathan-bq-162-papan-jadwal` or `agathan-bq-164-alur-pelanggan`. Push only with `git push origin agathan-bq-165-tampilan-pelanggan`, and only when the Tech Lead asks.
- If `agathan-bq-164-alur-pelanggan` gets new commits, merge it into this branch at the start of the next session. Never rebase a pushed branch.

## Batches and gates

The Tech Lead reviews at the end of each batch, in the browser at 390, 834 and 1440 px, before the next batch starts.

| Batch | Sessions | Why grouped |
|---|---|---|
| 1 | 1 | Foundation: derived colours, shape and surface tokens, shared components. Every screen depends on it |
| 2 | 2, 3 | Storefront and schedule, the first two booking steps |
| 3 | 4, 5 | Checkout, payment, success and invoice, status and error pages |
| 4 | 6 | After booking: manage and reschedule |
| 5 | 7 | Owner templates; starts only after D-028 is approved |
| 6 | 8 | Verification and docs |

## Rules for every session

1. Presentation only, except `StorefrontBranding` (session 1) and the Tampilan form (session 7). Controllers, Actions and Domain classes do not change. If a screen needs data that the controller does not pass, stop and record it under "Open findings".
2. Read `.ai/rules/frontend.md`, `.ai/rules/tests.md` and `docs/domains/public-site.md` first.
3. Colours, radii and shadows come only from the customer tokens (session 1). No raw hex values, Tailwind palette colours or `!important` in customer views.
4. Every screen is built three times on purpose: phone (< 768 px), tablet (768–1023 px) and desktop (≥ 1024 px), following spec §5 and §7. Use Tailwind `md:` and `lg:` breakpoints; structure may differ per breakpoint.
5. Copy is final in spec §7. Use customer words (BQ-164 copy rules still apply).
6. A failing test is evidence. When a test asserts old copy that this work item changes on purpose, update the assertion and name the spec section in the commit message. Never weaken a security, tenancy or access assertion.
7. Run after each session: `vendor/bin/pint --dirty --format agent`, `npm run build`, related tests, then the full suite once. Run PHPStan with PHP 8.4.25 and `-d opcache.enable_cli=0`.
8. Browser check each touched screen at 390, 834 and 1440 px with three colours: `#382186`, `#0F766E` and a light colour `#F59E0B`, and with all three button styles and card styles at least once. No horizontal scroll, no console errors.

## How to run and test locally

- App: `http://bookqu.test`, storefront `http://bookqu.test/brama-digital` (tenant 7).
- Change appearance in `/owner/booking-page` → Tampilan, or in the `tenants` row (`theme_color`, `button_style`, `card_style`, `font_family`, `banner_path`).
- Management pages need an access link: `php artisan booking:test-token --booking-id=<id> --tenant-id=7`. Do not paste the link into commits or docs.
- Mark a local SingaPay payment as paid: `php artisan singapay:simulate-webhook <order_id>` (`--status=failed` / `expired`). The local SingaPay sandbox refused tokens on 20 Nov 2026, so payment pages may need a seeded pending payment.

## Plan

### Session 1: foundation (spec §2, §3, §4, §6)

1. `app/Support/StorefrontBranding.php`: extend `for()` with
   - `onPrimary` threshold aligned to spec §2.1 (dark text when luminance > 0.18), plus `onPrimarySoft`, `onPrimaryFaint`, `onPrimaryLine`;
   - `primaryText` (brand, or brand mixed 42% with black when the brand is light) and `tint`, `tintStrong`;
   - a `shape` block from `button_style`: control, tile, area, chip, card and small-card radii (spec §3.1);
   - a `surface` block from `card_style`: card and raised shadows/borders (spec §3.2).
   Keep the existing keys so the owner preview keeps working until session 7.
2. `resources/views/customer/layouts/booking-shell.blade.php`: write every value as a CSS variable (`--sf-*` namespace) on `.customer-shell`. Load only the business font, weights 400–700.
3. `resources/css/app.css`: a customer layer that reads the `--sf-*` variables: `.sf-btn`, `.sf-btn-secondary`, `.sf-chip`, `.sf-field`, `.sf-area`, `.sf-tile`, `.sf-card`, `.sf-card-raised`, `.sf-card-small`, hero ornament and banner scrim, bottom bar, sheet. Remove the owner-token overrides in the customer layer that BQ-164 added for the old look.
4. Components in `resources/views/components/customer/` (reuse and rename where one already exists): `hero`, `step-header`, `bottom-bar`, `service-card`, `service-row`, `category-chips`, `date-strip` (scrollable with snap), `month-calendar`, `time-grid`, `sheet`, `summary-panel`, `ticket`, `status-note`, `empty-state`. Document props in `components/customer/README.md`.
5. Tests: `tests/Unit/StorefrontBrandingTest.php` covers the dark/light text switch, `primaryText` for a light brand, the radius table for each button style, the shadow table for each card style, and that unknown values fall back. A feature test asserts the storefront writes the `--sf-*` variables for a tenant with `pill` + `outline`.

### Session 2: storefront (spec §7.1)

- `customer/booking/program-selection.blade.php` and `partials/landing.blade.php` (keep the Pro landing include as it is).
- Phone: compact hero, sticky search and scrollable category chips, favourite carousel (`is_popular`), service list.
- Tablet: two-column hero with location card, wrapped chips, favourites in three columns, two-column service grid.
- Desktop: hero with raised location card, sticky category navigation, favourites in three columns, two-column grid with descriptions.
- States: pending payment bar (`pendingCheckout`), empty search, bookings closed (`acceptsOnlineBookings`), banner, service photos (`cover_image_url`).
- Title "Reservasi di {bisnis}"; phone `h1` is the business name.

### Session 3: schedule (spec §7.2)

- `customer/booking/schedule-selection.blade.php`, `components/customer/date-strip`, `booking-calendar`, `time-grid`, `time-slot-card`, `summary`, `public/js/booking-schedule.js` (presentation only).
- Phone: step header, service card over the hero, scrollable date strip with a "Kalender" button that opens the month sheet, three-column times, bottom bar.
- Tablet: month calendar beside times, bottom bar.
- Desktop: calendar, times and sticky summary in three columns.
- One price line per day from the existing price data; times no longer repeat the price.
- States: several consecutive sessions, loading skeleton, empty day with the nearest day that has sessions (only if the data is already available; otherwise record it), capacity ("Sisa n tempat" and participants).

### Session 4: checkout and waiting for payment (spec §7.3, §7.4)

- `customer/booking/checkout.blade.php`, `partials/booking/checkout-form.blade.php`, `partials/booking/checkout-summary-desktop.blade.php`, `public/js/booking-checkout.js`.
- `customer/booking/payment.blade.php` and `partials/payment/*` (pending card, action buttons, cancel modal).
- Phone: collapsible order summary, one-column form, add-ons with 44 px steppers, bottom pay bar. Tablet: summary in the header, two-column form, bottom pay bar. Desktop: form and sticky summary.
- Errors: summary box at the top, red field border and message, input kept (BQ-164 PL-02 stays).
- Waiting: remaining-time progress bar with a label, one "Cara bayar" box, checking state with `aria-busy`, collapsible reservation details on phone.

### Session 5: success, invoice, status and error pages (spec §7.5, §7.8, §7.9)

- `customer/booking/invoice.blade.php` (success and invoice modes), print styles, `partials/payment/expired-state.blade.php`, `failed-state.blade.php`, `customer/access.blade.php`, `errors/*.blade.php` and `errors/customer.blade.php`.
- Customer emails in `resources/views/emails/` follow the derived colours where the email layout allows inline colour; no new email content.

### Session 6: manage and reschedule (spec §7.6, §7.7)

- `customer/manage/details.blade.php`, `partials/manage/*` (timeline, review section, refund form), `customer/manage/reschedule.blade.php`, `public/js/booking-reschedule.js`.
- Manage uses grid areas per breakpoint and every state in spec §7.6, including the group payment state. The review form shows only for completed bookings, as today.
- Reschedule on tablet shows From → To in the header and a bottom bar with "Batal" and "Simpan jadwal baru". The refused state keeps the current booking unchanged and marks the taken time as full.

### Session 7: owner templates (spec §3.4) — waits for D-028

- `app/Support/StorefrontBranding.php`: `TEMPLATES` constant (Modern, Rapi, Tegas) and `templateFor(Tenant)` that returns the matching template or `custom`.
- `resources/views/owner/booking-page.blade.php`, section Tampilan: three template cards above the existing fields. Choosing one fills button style, card style and font in the form; the owner still saves. The live phone preview uses the new customer components so it matches the storefront.
- No change to `UpdateAppearanceRequest` validation or the database.
- Tests: `templateFor` for each template and for a custom mix; the Tampilan page renders the three templates and marks the current one.

### Session 8: verification and docs

- Browser pass of every screen and state at 390, 834 and 1440 px, with the three templates and the light colour. Record screenshots or notes in Evidence.
- Contrast check for the light colour on every brand surface.
- Full suite, PHPStan, `npm run build`.
- Docs: `.ai/rules/frontend.md` (customer rules from spec §10), `docs/domains/public-site.md` (appearance now covers every customer page), `docs/owner-portal.md` §8.9 (templates, after D-028), mark BQ-164 superseded for the look.

## Invariants at risk

- **INV-TEN-01, INV-TOK-01:** views only read data the controllers already pass; no new queries in Blade; access-link pages keep `CustomerAccessHeaders` and the private 404.
- **INV-BOOK-03, INV-SCH-01:** slot labels ("Penuh", "Sisa n tempat") and reschedule availability still come from `GetAvailableSchedules`, `AvailabilityRules` and `BookingRules`. The UI only displays them.
- Stored appearance values reach a `<style>` block; `StorefrontBranding` keeps normalising every value and falls back on anything unknown.

## Tests

- Unit: `StorefrontBrandingTest` for derived colours, shape and surface tables, fallbacks and (session 7) `templateFor`.
- Feature: storefront CSS variables per setting; copy assertions updated where spec §7 changes text; existing access, tenancy, payment and reschedule tests stay green unchanged.
- Manual: browser matrix in rule 8 and session 8.

## Docs to update

`.ai/rules/frontend.md`, `docs/domains/public-site.md`, `docs/owner-portal.md` §8.9 (after D-028), `docs/work/ROADMAP.md`, this file's Evidence.

## Evidence (fill when done)

### Session 1: foundation (21 Nov 2026)

- `StorefrontBranding::for()` adds `onPrimarySoft`, `onPrimaryFaint`, `onPrimaryLine`, `primaryText`, `tint`, `tintStrong`, `isLight`, `shape` (`SHAPES`, spec §3.1) and `surface` (`SURFACES`, spec §3.2). `onPrimary` follows spec §2.1: `#17151F` above luminance 0.18, else white. Existing keys stay for the owner preview and emails.
- `booking-shell` writes every value as `--sf-*` on `.customer-shell`, loads only the business font at 400–700, and uses the neutral page colours. The `--bq-*` values stay as a bridge for pages not yet rebuilt (see Open findings).
- `resources/css/app.css`: `:root` fallbacks and neutrals, `@theme inline` utilities (`bg-sf-*`, `text-sf-*`, `rounded-sf-*`, `shadow-sf-*`, `font-sf`), and the classes `sf-btn`, `sf-btn-secondary`, `sf-btn-on-primary`, `sf-chip`, `sf-nav-item`, `sf-field`, `sf-area`, `sf-tile`, `sf-card`, `sf-card-raised`, `sf-card-small`, hero ornament and banner scrim, `sf-progress`, `sf-bottom-bar`, `sf-sheet`, `sf-note`, `sf-skeleton`, `sf-scroll-row`, `sf-ticket-tear`. The focus ring no longer uses BookQu ink.
- Components (`components/customer/README.md`): new `hero`, `hero-ornament`, `step-header`, `bottom-bar`, `service-card`, `service-row`, `category-chips`, `month-calendar`, `sheet` (reuses `ownerDialog`), `summary-panel`, `ticket`, `status-note`, `empty-state`, `icon`; `date-strip` and `time-grid` rebuilt on `sf-tile` with their Alpine contract unchanged; unused `booking-calendar` and `time-slot-card` (raw hex) removed. Markup follows the canvas boards Layanan, Jadwal, Berhasil and Matriks; where the canvas uses uppercase labels, the spec's sentence case is used (§4).
- Tests: `Unit/StorefrontBrandingTest` (dark/light text, amber contrast, `primaryText` 42% with black, tints, radius table per button style, shadow table per card style, fallbacks); `Owner/HalamanBookingPageTest::test_storefront_writes_customer_tokens_for_the_saved_button_and_card_style` (amber + `pill` + `outline` + Inter); `Components/CustomerComponentsTest` (new components render prepared data without hex colours; sheet is a labelled modal dialog). `Customer/BookingTimeRangeAfterDurationChangeTest` now expects email text `#17151F` on a light brand (spec §2.1).
- Commands (PHP 8.4.25, `-d opcache.enable_cli=0`): `pint --dirty` passed; `npm run build` passed; related tests 28 passed; full suite 1040 passed (6919 assertions); PHPStan no errors.
- Browser (Laragon, `bookqu.test`): a temporary preview page in the customer shell (removed before commit) rendered every component with four settings: `#382186` rounded elevated Plus Jakarta Sans; `#0F766E` pill outline Inter; `#F59E0B` square soft Outfit; `#382186` with a banner and Lora. At 390, 834 and 1440 px: no horizontal scroll, no console errors; radii, shadows, fonts and on-primary colours measured as specified (amber: text `#17151F`, brand text `#674205`); phone h1 is the business name, tablet/desktop "Reservasi di …"; bottom bar hidden from 1024 px; banner shows the scrim with white text and hides the ornament. The real storefront `/brama-digital` still renders on the new shell at 390 px without overflow or errors. Sheet: opening moves focus to "Tutup" and Esc closes it (`open` false); the slide transition itself could not be watched because the browser pane was hidden.

## Open findings

- **Bridge kept (session 1, step 3).** The `--bq-*` values in the shell and the `.customer-shell[data-branded]`, `.bq-btn-primary`, `.customer-primary` and `.booking-card` overrides are still needed by the pages sessions 2–6 rebuild; removing them now would turn those pages BookQu purple. Remove them in session 6, when the last page uses the `sf-*` layer.
- **Scroll rows in grids.** A grid or flex item holding a `sf-scroll-row` needs `min-w-0`, otherwise the row widens the page (found in the preview at 390 px; noted in the components README).
- **Service icons.** Services have no icon or kind field; `service-card` and `service-row` take an `icon` name. Session 2 decides the mapping (for example by category) or keeps the default.
