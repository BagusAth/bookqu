---
id: BQ-164
title: Bring every customer-facing page and email to the owner portal standard
type: improvement
status: approved
priority: P1
phase: 4
domain: public-site
requirements: [FR-PUBLIC-001, FR-PUBLIC-002, FR-PUBLIC-003, FR-PUBLIC-004, FR-BOOKING-001, FR-BOOKING-002, FR-BOOKING-003, FR-BOOKING-004, FR-BOOKING-008]
invariants: [INV-TEN-01, INV-TOK-01, INV-BOOK-03, INV-SCH-01]
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: [BQ-162]
---

## Problem

The owner portal was rebuilt on one design system in BQ-162 (tokens in `resources/css/app.css`, `x-ui.*` components, Papan Jadwal rules). Everything a customer sees still uses an older look: raw Tailwind colours, hard-coded hex values and five different page layouts. It also has broken paths.

The audit [`docs/design/booking-pelanggan/audit-ui-2026-11-20.md`](../design/booking-pelanggan/audit-ui-2026-11-20.md) lists 32 findings (PL-01 to PL-32) with reproduction steps, files and lines. That document is the source of truth for every finding ID used below.

**Approval.** The Tech Lead approved this work item on 20 Nov 2026 when assigning it to Codex. These Tech Lead decisions are part of that approval:

- The owner portal is the visual reference. No separate customer prototype is built.
- The owner keeps choosing any theme colour (FR-PUBLIC-003). The server derives the readable text colour and the hover, light and border shades. No colour list is imposed on owners.
- `viewed` booking log events stay recorded for audit. Customer pages do not show them (PL-13).
- Saved contact data on the device is stored per business, and the customer can clear it (PL-18).
- Copy that promises what the system does not guarantee is removed (PL-12). This is UI copy only, with no product change.

**Out of scope:**
- the BookQu marketing site (`/`, login, register);
- the owner portal;
- SingaPay sandbox credentials (operations);
- self-service recovery of expired access links (deferred by BQ-001);
- any change to prices, availability, payment, cancellation or refund rules.

## Branch rule

All sessions work on `agathan-bq-164-alur-pelanggan`, created from the tip of `agathan-bq-162-papan-jadwal`, because this work reuses the BQ-162 tokens and components.

- Each session is one commit series, done in order.
- Nothing goes to `integration` until the Tech Lead decides.
- Never push to `integration`, `main` or `agathan-bq-162-papan-jadwal`. Push only with `git push origin agathan-bq-164-alur-pelanggan`.
- Before every push, check that `git rev-parse --abbrev-ref @{u}` is `origin/agathan-bq-164-alur-pelanggan` (or empty on the first push).

**When BQ-162 changes.** If `agathan-bq-162-papan-jadwal` gets new commits, merge it into this branch at the start of the next session. Never rebase a pushed branch.

## Batches and gates

The agent runs the sessions of one batch in order. After each session it commits, pushes and fills the evidence. The Tech Lead reviews at the end of each batch before the next batch starts.

| Batch | Sessions | Why grouped |
|---|---|---|
| 1 | 1 | Broken paths, fixed in the current markup before the redesign so they ship even if later batches wait |
| 2 | 2 | Foundation: tokens, brand derivation, one customer layout, error pages and components. Every later screen depends on it, so it is reviewed alone |
| 3 | 3, 4 | Booking flow and payment pages, the path a new customer walks |
| 4 | 5, 6 | After booking: manage, reschedule, invoice and email |
| 5 | 7 | Verification and docs |

If a session finds something this plan or the audit does not answer, it stops and writes the question under "Open findings" instead of deciding.

## Do not touch (owned by Developer B, BQ-162 follow-up)

These BQ-162 findings belong to Developer B ([pembagian](BQ-162/pembagian-perbaikan-dev-a-b.md) §4). Do not fix them here, even when the same file is open:

- T-14 (`APP_NAME`, vendor notification templates);
- T-15 (English validation messages on customer pages: `lang/id`);
- T-35 (quiet-hour promo refusal message in `app/Domain/Booking/BookingPricing.php`);
- T-22 (landing page banner asset).

If a customer page shows one of these problems, note "milik Developer B (T-xx)" in the evidence and move on.

## Rules for every session

1. **Read the rules first.** Read `AGENTS.md`, `.ai/rules/index.md` and every rule file that matches the paths you touch. Customer controllers fall under `booking-availability.md`, `http-layer.md` and `tenancy-security.md`. Payment pages fall under `payment.md`. Views fall under `frontend.md`. These rules override this plan.
2. **Thin controllers, presentation-only Blade.** Controllers stay thin (validate → Action → respond). Blade and Alpine stay presentation only. Move the logic listed in PL-31 (`Storage::url`, phone normalising, WhatsApp URLs, price formatting) into an Action or a small presenter in `app/Actions/Customer` or `app/Support`. Pass values into Alpine only with `@js` or `Js::from`.
3. **Use the shared rules.**
   - Availability through `AvailabilityRules`/`GetAvailableSchedules`.
   - Occupancy through `BookingRules`.
   - Times through `BookingTimeRange`.
   - Amounts through `App\Support\Rupiah`.
   - Status labels through `BookingState::formatLabel`, `PaymentState::formatLabel` and `PaymentMethodLabel`.
4. **Never edit a test just to make it pass.** If a test asserts old markup (a class, a label) and the behaviour is unchanged, update the assertion and say so in the commit message.
5. **Add tests for new behaviour** (`testing-best-practices` skill). Customer components get render tests in `tests/Feature/Components/CustomerComponentsTest.php`.
6. **Before each commit:** `vendor/bin/pint --dirty --format agent`, the related tests, and `npm run build`. **At the end of each session:** `php artisan test --compact` and `vendor/bin/phpstan analyse --memory-limit=1G`.
   - On this machine, run them with PHP 8.4 (GD enabled): `C:\laragon\bin\php\php-8.4.25-Win32-vs17-x64\php.exe`.
   - Run PHPStan with `-d opcache.enable_cli=0`.
7. **Check each touched page in the browser** at 375, 768, 1024 and 1366 px, with no horizontal scroll and no console errors. Use two tenant themes: the default colour, and a light colour such as `#FDE047` set temporarily on a local tenant and restored afterwards. Record what you checked in the session's evidence.
8. **Findings outside the session** go into "Open findings", not into the code.

## How to run and test locally

- App: `http://bookqu.test` (Laragon, MySQL `bookqu`). Storefront with data: `http://bookqu.test/brama-digital` (tenant 7, 10 services, slots two days ahead).
- **Management pages:** run `php artisan booking:test-token --booking-id=<id> --tenant-id=7`, then open the printed `/access#…` link. Never paste the link into a commit, a document or chat.
- **Payment pages:**
  - The local SingaPay sandbox currently refuses tokens (403), so checkout ends in "Gagal memproses pembayaran".
  - Prove the waiting, failed and expired states with feature tests and record the missing browser check.
  - If the Tech Lead fixes the credentials, run `php artisan singapay:simulate-webhook <order_id>` (`--status=failed` / `expired`) to walk each state.
- **Email:**
  - Render each Mailable in a feature test (`$mailable->render()`) and assert content.
  - For a visual check, write the rendered HTML to the scratch folder of the session (not the repository) and open it in the browser.
- **Test data hygiene:**
  - Bookings created during checks stay local. Use `@example.test` addresses only.
  - Never run `migrate:fresh --seed` on `bookqu`; Developer B owns seeder fixes (T-05, T-40).

## Customer design rules

These rules derive from the owner design rules in `.ai/rules/frontend.md`. Session 7 adds them to that file.

**Colour.** Colours come only from `bq-*` tokens.
- The tenant colour drives `--bq-primary` and its server-derived companions:
  - `--bq-primary-hover`;
  - `--bq-primary-light`;
  - `--bq-primary-border`;
  - `--bq-on-primary`, text on primary: dark or white by WCAG contrast, at least 4.5:1.
- No raw Tailwind palette colours, hex values or `!important` overrides in customer views.

**Type.**
- Font: the tenant font (`StorefrontBranding::FONTS`) for headings and body.
- Times and amounts use `bq-num`.
- Text is at least 13 px. Badges, step numbers and phone navigation labels may use 12 px.
- Sentence case; no uppercase micro-labels.

**Formats.** All formats come from the server:
- money as `Rp150.000`;
- time as `09.00 – 10.00`;
- dates as `Selasa, 24 Nov 2026` (locale `id`).

**Layout.**
- One customer layout for every page: business identity, one contextual back link, one WhatsApp help button, footer "Didukung oleh BookQu".
- Content lives in panels, not in a card per row.
- One primary action per screen.
- On a phone, the primary action sits in a bottom bar.

**States.**
- Every error keeps the customer's input and offers a way forward: retry, pick another time, or contact the business on WhatsApp.
- Every empty state says why it is empty and what to do next.

**Motion.**
- Motion appears only in answer to an action, and never under `prefers-reduced-motion`.
- No infinite animation, decorative gradient, hover lift or emoji.

**Copy.**
- Indonesian, from the customer's point of view.
- Customer copy never uses these words: `slot`, `reschedule`, `add-on`, `order ID`, `sukses`, `EWALLET`, `admin`.
- Use instead: "sesi", "ubah jadwal", "tambahan", "nomor pesanan", "Lunas", the payment method label, and the business name.

**Accessibility.**
- Every control has a name.
- Selected time buttons use `aria-pressed`.
- Status changes use one polite live region per page.
- Focus is visible.
- Touch targets are at least 44 px.

## Plan

Each session is one Codex run. Start every session by reading this file and the audit sections it names.

### Session 1: broken paths (PL-01, PL-02, PL-03, PL-09, PL-11, PL-13)

**PL-01.** Build every customer WhatsApp link to the business with `GetCustomerPanel::whatsAppUrl()` (or move that normaliser to `app/Support` and use it from both places). Do this from PHP, not Blade, in:
- `booking-shell`;
- `manage/show`;
- `manage/payment-show`.

Test: a business number `081299887766` yields `https://wa.me/6281299887766`.

**PL-02.** The checkout branch that returns `back()->with('error', …)` (`BookingCheckoutController.php:234`) keeps the input like the other branches: `withInput($request->except('checkout_fingerprint'))`.
- The page restores name, email, phone, note, participants, add-on quantities and promo code from `old()`.
- Test: a gateway failure redirects back with the old input, and the page renders it.

**PL-03.** Reschedule:
- When the slot endpoint returns no slots and a `message`, show that message in place of "Tidak ada slot…", with a WhatsApp button to the business.
- The month calendar must not mark a date as available when every slot on it is refused for this booking (for example a higher price). Decide availability on the server with the same rule the slot endpoint uses. Never decide it in JS.
- Test both.

**PL-09.** Every customer-facing time comes from `BookingTimeRange`, never from `bookings.jam` alone. This covers:
- manage booking;
- manage payment;
- reschedule "Jadwal saat ini" and its deadline;
- both invoices;
- the emails.

Test: a booking whose `jam` disagrees with its slot shows the slot's time everywhere.

**PL-11.** Replace the leaked internal labels with the shared label sources (rule 3 above):
- "LUNAS / SUKSES" → "Lunas";
- "EWALLET" → the payment method label;
- "1 slot" → "1 sesi";
- "(Reschedule)" removed;
- "ORDER ID" → "Nomor pesanan".

**PL-13.** The customer timeline hides `viewed` events. The owner slip keeps them. Test both views.

**Done when:**
- the tests above pass;
- `BookingManageTest`, `PaymentGroupManagementTest`, `BookingFlowTest`, `ThreeStepBookingFlowTest` and `CustomerFrontendSpecificationTest` are green;
- the three paths are checked in the browser at 375 px.

### Session 2: foundation (PL-05, PL-06, PL-07, PL-14, PL-15, PL-16, PL-31, PL-32)

**Brand.** `StorefrontBranding::for()` also returns:
- `hover`, `light` and `border` shades;
- `onPrimary`, the text colour with contrast ≥ 4.5:1 against the primary colour.

Old non-hex values (`purple`, `blue`, …) map to a hex value, and the mapping is written down in the class. Unit-test the contrast choice for a dark and a light colour, and the mapping.

**Tokens.** The customer layout sets the `--bq-primary*` variables and `--bq-on-primary` from those values in one `<style>` block. Remove the `!important` class overrides in `booking-shell.blade.php:35-58`.

**Layout.** Create one customer layout (`resources/views/layouts/customer.blade.php`, or rebuild `customer/layouts/booking-shell`). It provides:
- header: logo or initials (`mb_` functions, PL-32), business name, one back slot, WhatsApp help;
- the step bar slot;
- flash messages through `x-ui.banner`;
- footer "© {tahun} {bisnis} · Didukung oleh BookQu" (no security claims);
- print rules.

Every customer page moves to it in its own session. This session moves the storefront home, the error pages and `/access`.

**JavaScript.** Add a Vite entry `resources/js/customer.js` that starts Alpine. Do not use `app.js`, which carries landing code. Customer pages stop loading Alpine from jsDelivr. The service list renders without JS (PL-16). Alpine only adds search, filter and selection.

**Components.** Add `x-customer.*` components built on the `x-ui.*` primitives, with render tests:
- `page-header` (title + one line);
- `summary` (one booking summary: service, sessions, add-ons, promo, total);
- `stepper` (quantity −/+);
- `date-strip`;
- `time-grid`;
- `status`;
- `help-link`.

Update `resources/views/components/owner/README.md`, or add a `components/customer/README.md` next to it.

**Error pages.**
- Add `errors/404`, `errors/500` and `errors/503`, and restyle `403` and `419`.
- They use a minimal layout that does not need a tenant: BookQu identity, an Indonesian message, and a link back (the storefront when the URL has a known slug, otherwise `/`).
- Tests: 404 for an unknown slug and for an unknown management code renders the Indonesian page without leaking whether the code exists (INV-TOK-01).

**`/access`.**
- Show a small styled card with "Membuka reservasi Anda…" and a progress indicator.
- On failure, show "Tautan tidak berlaku atau sudah kedaluwarsa." with what to do next (contact the business through the link in the email, or open the business page).
- Keep the script contract (hash read, `replaceState`, POST, redirect) unchanged. `CustomerAccessTest` stays green.

**Done when:**
- component render tests and the brand tests pass;
- `CustomerAccessTest`, `CustomDomainBookingTest` and `CustomerFrontendSpecificationTest` are green;
- the storefront home, `/access` and the error pages are checked at four widths with both themes.

### Session 3: booking flow (PL-04, PL-10, PL-12, PL-18, PL-19 to PL-27, PL-29, PL-30 for these pages)

**Pilih layanan.**
- Show services as a panel list or a calm grid built from the components.
- The image is optional. Without an image, show no placeholder text.
- "Favorit" is a small sentence-case badge.
- Duration and price use `bq-num`.
- Truncated descriptions carry `title` or a "Selengkapnya" toggle.
- Search and category filter remain.
- No staggered entrance animation.

**Jadwal.**
- Use `x-customer.date-strip` + `x-customer.time-grid`.
- Times read `09.00 – 10.00`.
- Past times today are hidden.
- Full times show "Penuh".
- "Sisa n tempat" shows only when capacity > 1.
- One continue label: "Lanjutkan" with the session count in the summary.
- The summary shows only real lines: no "Biaya Layanan Rp0".

**Checkout.**
- One heading and one sentence instead of three.
- "Jumlah peserta" shows only when capacity > 1.
- "Tambahan" lists each item with its price and a −/+ stepper.
- The quote refreshes automatically (debounced, about 400 ms) through the existing `validate-voucher` endpoint. The manual "Perbarui ringkasan harga" button goes away.
- While a quote is loading or stale, the pay button is disabled with the reason shown, and the total in the bottom bar says "Menghitung…".
- One summary block, `x-customer.summary`, is used on desktop and in the phone sheet.
- Session price is labelled "Harga sesi", not "Biaya Layanan".
- Remove "Sudah termasuk pajak".

**Saved contact (PL-18).**
- The storage key includes the business slug.
- The checkbox text says "Simpan nama, email dan nomor di perangkat ini untuk {bisnis}".
- Add a "Hapus data tersimpan" link.

**Done when:**
- `BookingFlowTest`, `ThreeStepBookingFlowTest`, `BookingFlowHardeningTest`, `CustomerFrontendSpecificationTest` and `SafeOperationErrorsTest` are green;
- new tests cover:
  - participants hidden at capacity 1;
  - the add-on price rendered;
  - the server rejecting a stale fingerprint, which stays unchanged;
- a full booking up to the payment request is walked at four widths in both themes. Payment creation may fail locally (see "How to run").

### Session 4: payment pages and invoices (PL-10, PL-11, PL-19 to PL-21 for these pages)

**Payment pages.** `customer/booking/payment` and `partials/payment/*`:
- waiting state with total and a countdown in `bq-num`;
- steps as a numbered list at ≥ 13 px;
- failed and expired states with one primary action ("Pilih jadwal lagi" back to the storefront) and WhatsApp help;
- the cancel dialog through the shared confirm pattern.

Keep the polling and status logic exactly as it is (`payment.md` rules). This session changes only the presentation.

**Invoices.** `customer/booking/invoice`, `customer/manage/invoice`, `customer/manage/payment-invoice`:
- one invoice layout: business identity, invoice number, customer, sessions with `BookingTimeRange` times, items, total, payment method label;
- print-friendly, with no gradients;
- the tenant font, falling back to a system font in print.

Remove "Bukti reservasi resmi" and "sah dari sistem" claims.

**Done when:**
- `CancelCheckoutPaymentTest`, `PaymentGroupManagementTest` and the payment status tests are green;
- render tests cover the waiting, failed and expired states;
- invoices are checked at 375 px and in print preview.

### Session 5: after booking (PL-07, PL-08, PL-12, PL-28, PL-30)

**Manage pages.** `customer/manage/show` (one booking) and `customer/manage/payment-show` (one payment group) share one page pattern:
- status at the top (`x-customer.status`);
- the session block with date and times;
- payment summary;
- customer data;
- actions;
- history (without `viewed`, from session 1);
- business location and contact.

Remove:
- the dark gradient ticket;
- the monospace code styling (keep a copy button for the booking code);
- the "Admin" label;
- the "Simpan tautan ini" note. Replace it with one line: "Buka reservasi ini lagi lewat tautan di email Anda."

**Actions follow the business policy:**
- Ubah jadwal with its deadline.
- When refunds or cancellation are off, one sentence and the WhatsApp button, not an empty card.
- Google Calendar, share and invoice as secondary links.

**Reschedule.** Use the same `x-customer.date-strip` + `x-customer.time-grid` as the booking flow:
- the current session is marked;
- the server message from session 1 is shown;
- one confirm button in the bottom bar;
- the summary states the price difference rule from the server.

Fix the 375 px layout (PL-08).

**Review section.** The review form keeps its behaviour and uses the components.

**Done when:**
- `BookingManageTest`, `PaymentGroupManagementTest`, `BookingReviewTest` and `CustomerAccessTest` are green;
- both manage pages and reschedule are checked at four widths in both themes.

### Session 6: email (PL-17, PL-09 for email)

**One email layout** (`resources/views/emails/layouts/customer.blade.php`) used by `booking-confirmed`, `booking-rescheduled`, `booking-cancelled` and `booking-group-invoice`:
- table-based and inline-safe;
- no gradients, no flex, no emoji;
- business name and logo (absolute URL, only when present);
- tenant colour as a solid button with the derived `onPrimary` text.

**Content:**
- every session with `BookingTimeRange` times;
- the business address and map link when present;
- total with `Rupiah`;
- dates with `->locale('id')`;
- actions limited to what the business policy allows (do not promise cancel when it is off);
- the footer without a `href="#"` link.

**Data.** Mailables pass prepared data to the views. Views do not read relations. Keep the access-link delivery contract from `customer-access.md` unchanged: the link is minted at send time.

**Done when:**
- a render test per Mailable asserts business name, every session time, the total and the action link;
- `BookingConfirmedMail`, `BookingRescheduledMail`, `BookingCancelledMail` and `BookingGroupInvoiceMail` render in the browser from the scratch HTML at 375 px and 600 px.

### Session 7: verification and docs

**Checks:** full suite (SQLite), MySQL suite (`vendor/bin/phpunit -c phpunit.mysql.xml`), PHPStan, Pint and `npm run build`.

**Browser pass.** Every customer page at 375, 768, 1024 and 1366 px in two themes, plus `prefers-reduced-motion`:
- storefront;
- schedule;
- checkout;
- payment states (or tests where the sandbox refuses);
- `/access`;
- manage booking;
- manage payment;
- reschedule;
- three invoices;
- error pages.

Record any differences from the owner portal style in Evidence.

**Screen reader.** Do a keyboard-only pass of the booking flow. The NVDA or VoiceOver pass stays open for a person.

**Docs to update:**
- `.ai/rules/frontend.md`: add "Customer views" with the customer design rules above;
- `docs/domains/public-site.md`: storefront look and branding derivation;
- `docs/domains/customer-access.md`: `/access` page and the hidden `viewed` events;
- `docs/domains/notifications.md`: customer email layout;
- the audit file: status per PL item, like `BQ-162/pembagian-perbaikan-dev-a-b.md`.

**Done when:** all checks are green and the Tech Lead has the evidence to decide on merging.

## Prompt for each Codex batch

Paste the block below and change only the batch number (1–5):

```text
Repository C:\laragon\www\bookqu.
Batch 1 only: first run
  git fetch origin
  git switch -c agathan-bq-164-alur-pelanggan origin/agathan-bq-162-papan-jadwal
(if the branch already exists: git switch agathan-bq-164-alur-pelanggan; git pull origin agathan-bq-164-alur-pelanggan).
Later batches: git switch agathan-bq-164-alur-pelanggan; git pull origin agathan-bq-164-alur-pelanggan; then merge origin/agathan-bq-162-papan-jadwal if it has new commits.

Do BQ-164 batch N only (see "Batches and gates" in docs/work/BQ-164-alur-pelanggan.md).
Read first: AGENTS.md, docs/work/BQ-164-alur-pelanggan.md, docs/design/booking-pelanggan/audit-ui-2026-11-20.md, .ai/rules/index.md and every rule file matching the paths you touch.
Use the owner portal as the visual reference: resources/css/app.css tokens, resources/views/components/ui/*, resources/views/components/owner/README.md, and docs/design/papan-jadwal/prototype (php -S 127.0.0.1:8770 -t docs/design/papan-jadwal/prototype, route #/sistem).
Run the app at http://bookqu.test (storefront /brama-digital). Follow "How to run and test locally" and "Rules for every session" exactly.
Do not touch the Developer B items listed under "Do not touch".
Do the sessions of the batch in order. After each session: run its checks, fill its Evidence block in the work item, commit (one commit per finding group, mention the PL-xx IDs), and push only with git push origin agathan-bq-164-alur-pelanggan after checking the upstream.
Stop and report instead of guessing when the plan or the audit does not answer; write the question under "Open findings". Do not start a later batch.
```

## Invariants at risk

- **INV-TOK-01** (access grants): `/access`, error pages and manage pages must not reveal whether a booking code exists, put credentials in HTML or URLs, or widen a grant. `CustomerAccessTest` guards this.
- **INV-TEN-01:** every new presenter or Action reads data through the resolved tenant only. Saved contact data in the browser is keyed per business.
- **INV-BOOK-03 / INV-SCH-01:** slot labels ("Penuh", "Sisa n tempat") and reschedule availability come from `GetAvailableSchedules`/`AvailabilityRules`/`BookingRules`. The UI only displays them.
- **Payment (INV-PAY-*):** payment pages change presentation only. Polling, status checks and cancel keep their current endpoints and rules.

## Tests

Listed per session above. Every session ends with the full suite. Session 7 also runs the MySQL suite.

## Docs to update

See session 7. Do not edit `docs/requirements.md` or `docs/product.md`: no requirement changes.

## Evidence (fill when done)

- Session 1: PL-01, PL-02, PL-03, PL-09, PL-11, PL-13. PHP 8.4.25: Pint, Vite build, PHPStan and related PHPUnit checks passed (90 tests, 690 assertions). Full SQLite suite: 1,021 passed, 6,747 assertions. Browser: owner design reference consulted; the storefront, checkout, single and group management, reschedule and both management invoices fit 375, 768, 1024 and 1366 px with the default and light tenant themes, with no console errors on the final pass. Local checkout reproduced the SingaPay failure and retained the customer's email. The calendar reported zero available sessions on a higher-price-only date, and the empty slot response showed the server message and WhatsApp recovery in the browser. The browser used the local store and the documented test management grant.
- Session 2: PL-05, PL-06, PL-07, PL-14, PL-15, PL-16, PL-31, PL-32. Pint, Vite and PHPStan passed. The related suite passed (74 tests, 662 assertions). The full SQLite run had 1,024 passes and two presentation assertion failures. The assertions now check the bundled Alpine asset and JSON-escaped route values, and the targeted rerun passed. At 375, 768, 1024 and 1366 px the storefront, schedule, access recovery, and both live 404 variants fit in default and light themes. The 403, 419, 500 and 503 templates were rendered in Chromium at the same widths. Service selection worked with JavaScript disabled.
- Session 3: PL-04, PL-10, PL-12, PL-18, PL-19–PL-27, PL-29, PL-30. Pint, Vite and PHPStan passed. The full SQLite run had 1,024 passes and two failures for the existing past-session response contract. The responses still carry the server's past-session flag and the picker hides those rows. The final related rerun passed 86 tests with 682 assertions, including both compatibility cases. Chromium checked storefront, schedule and checkout at all four widths in default and light themes. In both themes, an add-on triggered a server quote automatically, the payment action waited for the quote, and checkout submitted to the local sandbox and retained the email on the 403 recovery path. The mobile summary opened and closed by keyboard, and the tenant-scoped contact entry could be deleted.
- Session 4: PL-10, PL-11, PL-19–PL-21. Pint, Vite and PHPStan passed. The full SQLite run had 1,026 passes and one guardrail failure for a duplicated countdown literal. Reusing the remaining seconds resolved it. The guardrail and payment render rerun passed 18 tests with 88 assertions. The related suite passed 186 tests with 1,166 assertions and rendered waiting, failed and expired payment states without a gateway call. Payment browser check pending: sandbox 403. Chromium checked all three invoices at 375, 768, 1024 and 1366 px with default and light themes, and at 375 px in print media. The print view retains the amounts and hides interactive controls.
- Session 5:
- Session 6:
- Session 7:

## Open findings

- Diputuskan agen, perlu review Tech Lead: PL-11 uses the customer option of the shared payment label formatter for “Lunas”. The existing owner label is “Berhasil”.
- Diputuskan agen, perlu review Tech Lead: an invalid access link has no verified business identity. Recovery points back to the original confirmation email and offers the public BookQu home.
- Diputuskan agen, perlu review Tech Lead: PL-18 starts fresh per tenant and ignores unscoped contact data from the legacy browser key. The old data has no tenant identifier to assign it safely.
- Session 4: The existing payment script can run the zero-time countdown branch when a failed payment also has a past deadline. Live browser confirmation is pending with the sandbox. Preserve the polling logic for this presentation batch.
