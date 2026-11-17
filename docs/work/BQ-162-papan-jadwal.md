---
id: BQ-162
title: Rebuild the owner portal on the "Papan Jadwal" prototype
type: improvement
status: approved
priority: P1
phase: 4
domain: booking
requirements: []
invariants: [INV-TEN-01, INV-BOOK-01, INV-BOOK-02, INV-BOOK-03, INV-BOOK-04, INV-BOOK-05, INV-SUB-01]
lane: heavy
approval: product-lead
decision: D-025, D-026
launch-blocker: true
depends-on: [BQ-161]
---

## Problem

BQ-161 restyled the owner portal but kept most pages on raw controls and Tailwind steps (`docs/design/papan-jadwal/audit-ui.md` §3, gaps G-1 to G-11). A review (`review.md`) and a static prototype (`docs/design/papan-jadwal/prototype/`) now define the target: a time-first layout, five-column tables, one name per action, and readable phone and tablet layouts. D-025 approves building all of it, including the content changes (B) and new features (C) listed in `docs/design/papan-jadwal/KEPUTUSAN.md`.

**Branch rule (D-025).** All sessions work on `agathan-bq-162-papan-jadwal`, one commit series per session, in order. Nothing goes to `integration` until the Tech Lead decides. Do not push to `integration` or `main`. Push only with `git push origin agathan-bq-162-papan-jadwal`; first check that `git rev-parse --abbrev-ref @{u}` is `origin/agathan-bq-162-papan-jadwal` (or empty), never `origin/integration`.

**Release (D-026 point 8).** v1.0.0 waits for BQ-162. The BQ-158 regression pass runs after BQ-162 is merged into `integration`.

**Batches and gates.** Sessions run in four batches; inside a batch the agent runs the sessions in order, commits and pushes after each session, and fills its evidence. The Tech Lead reviews at the end of each batch before the next batch starts.

| Batch | Sessions | Why grouped |
|---|---|---|
| 1 | 1 | Foundation: every later screen depends on the shell and primitives, so it is reviewed alone |
| 2 | 2, 3 | Shared daily parts and the daily pages that use them |
| 3 | 4, 5, 6 | Independent page groups on the same components |
| 4 | 9 | Verification and docs |

Sessions 7 and 8 are not part of any batch yet. Sessions 7 and 8 start only after the Product Lead has confirmed D-026 in `docs/product-decisions.md`; use the numbers from D-026, never guess. If a session finds something the plan or prototype does not answer, stop and write it under "Open findings" instead of deciding.

## How to use the prototype

- Run it: `php -S 127.0.0.1:8770 -t docs/design/papan-jadwal/prototype`, open `http://127.0.0.1:8770/#/beranda`. The "Prototipe" button switches role, plan, state (Berjalan, Baru, Berakhir, Memuat, Galat), data (Biasa, Ramai), density, sidebar and change labels.
- `#/sistem` is the component catalogue. `prototype/HANDOFF.md` maps prototype components to `x-ui` names; `prototype/css/tokens.css` holds every value.
- The prototype is a **reference, not code**: never copy its JavaScript or class names into the app. Rebuild each screen with Blade components, Alpine and Tailwind tokens, and take the Indonesian copy from it word for word.
- Data shown in the prototype is fake. Every number on a real page comes from an existing Action or a new Action in `app/Actions`; Blade stays presentation only.

## Rules for every session

1. Read `AGENTS.md`, `.ai/rules/index.md` and every rule file that matches the paths you touch. They override this plan.
2. Controllers stay thin (validate → Action → respond). No queries or business decisions in Blade or Alpine. Roles only through `TenantPermission`, plan limits only through `EntitlementRules`, occupancy only through `App\Domain\Booking\BookingRules`, state changes only through `BookingState::canTransition`.
3. Every page keeps its existing feature tests green. **Never edit a test just to make it pass.** If a test asserts old markup (a class, a label), update the assertion to the new markup only when the behaviour is unchanged, and say so in the commit message.
4. Add or update tests for new behaviour (`testing-best-practices` skill). Component variants get render tests in `tests/Feature/Components/UiComponentsTest.php`.
5. Before each commit: `vendor/bin/pint --dirty --format agent`, the related tests, and `npm run build`. At the end of each session: `php artisan test --compact` and `vendor/bin/phpstan analyse --memory-limit=1G`.
6. Check each touched page at 375, 768, 1024 and 1366 px as Pemilik and Kasir (seed accounts), no horizontal scroll, no console errors. Record what you checked in the session's evidence block below.
7. Findings outside the session go into "Open findings" at the end of this file, not into the code.

## Design rules (from the prototype)

Values live only in tokens (`resources/css/app.css`). Text minimum 13 px (12 px only for badges, tab counts, phone nav labels). Table rows 52 px (one line) / 60 px (two lines); at most five data columns; status as dot + text, badge only when it needs attention; codes, email and phone live in the slip or panel. Phone (< 768 px): bottom navigation, rows of at most two lines, sheets slide up. Tablet (768–1199 px): sidebar becomes a 72 px icon rail with titles; below 1024 px content is one column. Every truncated text has a `title`. Row actions are visible without hover on touch screens. One primary button and at most one banner per screen. Full list: `prototype/README.md` and `riwayat/rencana-keterbacaan-prototipe.md` ("Aturan desain baru").

## Plan

Each session is one Codex run. Start every session by reading this file, `KEPUTUSAN.md` and the prototype routes listed for it.

### Session 1: tokens, shell and primitives

- Decided by the Tech Lead (17 Nov 2026): light sidebar, Barlow Semi Condensed allowed.
- Tokens: map `prototype/css/tokens.css` to `resources/css/app.css` (colours, type scale as named classes, spacing, radius, density). Add Barlow Semi Condensed (500, 600) to the owner layout font link for clock times and headline numbers only.
- Shell (`components/owner/sidebar`, `topbar`, `bottom-nav`, `layouts/owner-layout`): light sidebar, tablet icon rail 768–1199 px, top bar with one "Catat booking" button, phone bottom nav with "Catat" in the middle. Rename "Walk-in" and "+ Booking" to "Catat booking" everywhere (B-10), plus "Tambahan" and "Halaman depan".
- New `x-ui` primitives with render tests: `table` (subgrid, group rows, selectable rows, phone two-line form, row action), `status-dot`, `kv`, `banner`, `error-block`, `menu`, `segmented`, `checkbox`, `radio-card`, `switch`, `sheet`, `form-section`, `stat` (one main number + supporting numbers), `table-note`, `mini-bar`, `duration`, `empty`, `skeleton`. Update `components/owner/README.md`.
- Prototype routes: `#/sistem`, any page for the shell.
- Done when: the shell matches the prototype at all four widths for Pemilik and Kasir, `OwnerAppShellTest` and `OwnerSidebarNavigationTest` are green, and every new component has a render test.

### Session 2: shared daily parts

- `x-ui.day-rail` (Beranda timeline with "now" line, empty slots as "+ Catat booking") and `x-ui.day-grid` (time-scaled calendar grid; column chooser: services with bookings or slots today, at most 6, rest through "Layanan n dari m").
- Booking slip (`booking-panel`): status, large time, customer with WhatsApp, payment block, payment deadline, history with actor and role, actions by status and role.
- Catat booking (`walkin-form`): one form for every entry point; crossed-out taken times, remaining seats, existing-customer suggestions after two letters, payment method.
- Reschedule, cancel / request cancellation (Kasir), refund decision, customer panel.
- Error states (T3): slot just taken (catch the existing conflict from the booking write and show `error-block` with nearest free times, keeping the input), save failed (toast with "Coba lagi", layer stays open), session expired (existing `session-expired`), booking quota reached, unconfirmed payout submission.
- Prototype routes: `#/beranda`, slip from `#/booking/mendatang`, "Catat booking" from the top bar, state = Galat.
- Done when: `BookingManagementTest`, `SharedBuildingBlocksTest` and the walk-in tests are green; a test proves the conflict path returns the friendly error without creating a booking (INV-BOOK-04).

### Session 3: daily pages

- Beranda (B-01): Perlu Anda first, one main number + supporting numbers, today + tomorrow, guide as card, one banner by priority. Keep the 60-second polling (`owner/dashboard/polling`).
- Kalender (B-02, C-05): day / week / month, per service or per unit (Pro), filters collapsed on phone.
- Booking (B-03): task tabs with counts, five columns, group per date, row actions in "Perlu tindakan", 25 rows per page, export for Medium+.
- Global search (audit T7): the top-bar box opens a result list grouped Booking / Pelanggan, at most five each, Enter opens the slip; reuse `owner/search` (`OwnerShellController::search`). Not in the prototype; follow the list-row style.
- Pelanggan (B-04, C-06): five columns, segment "Lama tidak datang", WhatsApp greeting (opens the owner's WhatsApp; the system sends nothing).
- Prototype routes: `#/beranda`, `#/kalender/*`, `#/booking/*`, `#/pelanggan/*`; also data = Ramai.
- Done when: `BerandaPageTest`, `KalenderPageTest`, `BookingManagementTest`, `PelangganPageTest`, `OwnerDashboardPollingTest` are green.

### Session 4: setup pages

- Layanan (B-05) with Kategori and Tambahan tabs; Unit (B-06); Jam Buka & Harga (four tabs, price simulator showing the rules behind each price).
- Split large views per tab into partials while migrating (`services.blade.php` 548 lines).
- Prototype routes: `#/layanan/*`, `#/unit`, `#/jam-buka/*`.
- Done when: `LayananPageTest`, `ScheduleManagementTest`, `SingleSlotManagementTest`, `ServicesDomainEndToEndTest` are green.

### Session 5: money pages

- Pembayaran (B-08): five tabs, three-step payout with the existing idempotency key; Laporan (B-09, C-04, C-10, C-11): one period filter, five tabs, two main numbers + strip, comparison with the previous period, one conclusion sentence per tab computed in the Action; Promo; Ulasan.
- Prototype routes: `#/pembayaran/*`, `#/laporan/*`, `#/promo/*`, `#/ulasan`.
- Done when: `PembayaranPageTest`, `LaporanPageTest`, `AnalyticsExportAndAggregationTest`, `UlasanPageTest` are green and new report figures have Action tests.

### Session 6: remaining pages

- Halaman Booking (B-07), Pengaturan (six tabs, role-dependent), Paket & Tagihan, checkout pages (restyle only), Notifikasi (grouped by day), Bantuan.
- Prototype routes: `#/halaman-booking/*`, `#/pengaturan/*`, `#/paket`, `#/notifikasi`, `#/bantuan`.
- Done when: `HalamanBookingPageTest`, `SettingsTest`, `PaketNotifikasiBantuanTest`, `LandingPageAndDomainTest`, `ApiAndWebhooksTest` are green.

### Session 7: booking automation (heavy, C-01 to C-03)

Gate: D-026 confirmed by the Product Lead. Values: slots 30 days ahead per service (opt-in, all plans); auto-complete 6 hours after the session ends (on by default, can be switched off, skips no-show and refund requests); bulk complete for Pemilik, Manajer and Kasir.


- First add the requirements (`docs/requirements.md`) and update `docs/domains/booking.md` and `docs/domains/schedule.md`; the code follows them.
- C-01 slot automation: per-service setting, nightly scheduled command that opens slots from opening hours up to the configured horizon, skips closed dates and existing slots, idempotent.
- C-02 auto-complete: setting in Kebijakan; scheduled command completes `paid` bookings X hours after the session ends, only through `BookingState::canTransition`, skips bookings with a refund request.
- C-03 bulk complete: selected rows and "Tandai selesai semua", one Action that applies the same transition rules per booking and reports skipped ones.
- Done when: unit tests for the rules, feature tests for the commands and the bulk Action, MySQL suite green for anything that locks.

### Session 8: growth features (heavy, C-07 to C-09)

Gate: D-026 confirmed. Values: weekly email Monday morning to Pemilik only (on, can be switched off); sources Instagram, WhatsApp, Google Maps, QR, direct (report Medium+); quiet-hour promo percent or amount on owner-chosen days and hours (Medium+), suggestion shown in Laporan → Okupansi. Update the plan table in `docs/product.md` (D-017) first.


- Requirements and domain docs first, as in session 7.
- C-07 weekly email summary: preference in Notifikasi, scheduled job, queued mail.
- C-08 booking source per channel: new nullable column set from a link parameter on the booking page (`?dari=instagram` etc.), shown in Laporan and as a Booking filter. Migration + tenant-safe query.
- C-09 quiet-hour promo: promo rule limited to hours; pricing goes through the existing price rules and payment amount checks.
- Done when: migrations run up and down, tests cover tenant isolation and price calculation, payment tests stay green.

### Session 9: verification and docs

- Full suite, MySQL suite, PHPStan, Pint, `npm run build`.
- Screen-reader pass (audit T9) on the shell, Booking, slip and Catat booking with NVDA or VoiceOver; fix missing names and live regions.
- Screenshots of every owner page at 375, 768, 1024, 1366 as Pemilik, Manajer and Kasir, compared with the prototype; list differences in Evidence.
- Update `docs/owner-portal.md` §8 (content) and §9 (visual system, sidebar), `.ai/rules/frontend.md` (component-first rule, design rules above), and the domain files changed by sessions 7–8.
- Done when: all checks green and the Tech Lead has the evidence to decide on merging into `integration`.

## Prompt for each Codex batch

Paste and change the batch number:

```text
Repository C:\laragon\www\bookqu, branch agathan-bq-162-papan-jadwal (git switch agathan-bq-162-papan-jadwal; git pull origin agathan-bq-162-papan-jadwal).
Do BQ-162 batch N only (see "Batches and gates"). Read docs/work/BQ-162-papan-jadwal.md, docs/design/papan-jadwal/KEPUTUSAN.md and AGENTS.md first.
Run the prototype with php -S 127.0.0.1:8770 -t docs/design/papan-jadwal/prototype and use the routes listed for each session.
Do the sessions of the batch in order. After each session: run its checks, fill its "Evidence", commit, and push only to origin agathan-bq-162-papan-jadwal.
Stop and report instead of guessing when the plan does not answer. Do not start sessions 7 or 8.
```

## Invariants at risk

- **INV-BOOK-01/02** (state changes): sessions 2, 3 and 7 add new entry points (bulk, automatic). All go through `BookingState::canTransition`; tests prove skipped and refused rows.
- **INV-BOOK-03/04/05** (occupancy, no double booking): Catat booking, reschedule and slot automation must use `BookingRules` and the existing locked write; the conflict UI only reports what the domain refused.
- **INV-TEN-01** (tenant isolation): new queries (reports, source, summaries) are tenant-scoped; add a cross-tenant test per new Action.
- **INV-SUB-01** (entitlement): Pro-only parts (units, per-unit calendar, landing page, domain) stay gated by `EntitlementRules`.

## Tests

Listed per session above. Every session ends with the full suite; sessions 7 and 8 also run `vendor/bin/phpunit -c phpunit.mysql.xml`.

## Docs to update

`docs/owner-portal.md` §8–§9, `.ai/rules/frontend.md`, `docs/requirements.md` (C items), domain files `booking.md`, `schedule.md`, `insights.md`, `catalog.md` (promo) and `notifications.md`, `components/owner/README.md`. Session 9 owns the final pass.

## Evidence (fill when done)

- Session 0 (prototype, 17 Nov 2026): prototype moved into the repo; audit P1 closed (tablet rail and one-column layout, error states, busy data, touch actions, full titles); checks `alat/cek.py` (44 routes × 3 switch sets, 0 issues), `alat/tepi.py lebar` and data = Ramai at 375/768/1024/1366 (0 issues), `alat/uji4.py` (9 error-state checks pass).
- Session 1 (17 Nov 2026):
  - Ran the PHP prototype on port 8770 and opened `#/sistem` and `#/beranda`. Compared the live shell with the reference as Pemilik and Kasir at 375, 768, 1024 and 1366 px. Live portal checked with temporary local SQLite seed accounts (MySQL was offline). Edge passed 56 page/viewport checks across Beranda, Booking, Kalender, Pelanggan and the Pemilik-only Layanan, Tambahan, Halaman depan, its preview, Laporan and Paket & Tagihan. The shell had the 72 px tablet rail, 232 px desktop sidebar and phone bottom nav, with no page-level horizontal overflow or console errors. The real BookQu mark and phone account trigger were retained.
  - Verified Catat booking opens from the visible shell button and returns focus on Escape at each width. The grouped selectable table rendered at 60 px in its two-line variant and its phone action menu supported arrow keys and Escape. Fixed the shared booking panel's literal Blade directive in Alpine initialization, which had produced a console error on every owner page.
  - Pint, `npm run build`, PHPStan and the focused suite passed (90 tests, including `OwnerAppShellTest`, `OwnerSidebarNavigationTest` and `UiComponentsTest`). The full suite on PHP 8.5 reported 982 passed and 4 failed. The three image tests passed on PHP 8.4.25 with GD (20 assertions). The documentation guardrail failure is recorded below.
- Session 2 (17 Nov 2026):
  - Ran the updated prototype on port 8770 at `#/beranda`, `#/booking/mendatang`, `#/kalender/hari` and `#/pembayaran/saldo` in state Galat. Edge passed 36 page/viewport checks at 375, 768, 1024 and 1366 px as Pemilik and Kasir using the local SQLite seed. Checked the booking slip from the calendar, the walk-in conflict keeping contact input and offering an alternative, and a lost payout response followed by a retry with the same request key and an inline amount error.
  - Pint, Vite build, PHPStan, the 95 related tests and the two day-component render checks passed. The walk-in regression asserts 409, the two nearest available times and no new booking/payment. The payout regression sends the same JSON request twice and asserts one payout and one balance hold. The full suite passed with PHP 8.4.25 (GD enabled), 988 tests and 6,537 assertions.
- Session 3 (17 Nov 2026):
  - Compared the prototype in data state Ramai at `#/beranda`, Kalender day/week/month and per unit, Booking Perlu tindakan/Mendatang, and Pelanggan Semua/Lama tidak datang. Edge passed 72 page/viewport checks at 375, 768, 1024 and 1366 px as Pemilik and Kasir on the local SQLite seed, with no horizontal page overflow or console errors. Enter opened the booking slip from global search. Sapa prepared the editable WhatsApp link.
  - Per-unit columns use the active units linked to the service (ADR-0017). Pelanggan uses the existing 60-day booking rule. The greeting uses the business link without promising specific available times.
  - Pint, Vite build, PHPStan and the related suite passed (79 tests, 558 assertions), including Pro/Small calendar modes and the dormant-customer WhatsApp link. Full suite on PHP 8.4.25 with GD passed (989 tests, 6,547 assertions).
- Session 4:
- Session 5:
- Session 6:
- Session 7:
- Session 8:
- Session 9:

## Open findings

- The full-suite documentation guardrail reports two undefined work item references in `docs/design/papan-jadwal/audit-ui.md`. Both references were already present on the branch before Session 1. Resolved in commit `eb277e4`.
- Resolved by the Tech Lead. A network failure, timeout or 5xx during payout submission shows “Pengajuan pencairan belum terkonfirmasi”. “Coba lagi” sends the same `request_key` and “Nanti saja” closes the dialog. Validation remains inline. The local balance hold and later admin transfer continue under FR-PAYOUT-002 and FR-PAYOUT-003. Prototype updated in `eb277e4`.
