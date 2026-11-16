---
id: BQ-161
title: Give the owner portal a visual design system and restyle every owner page
type: improvement
status: approved           # Tech Lead 2026-11-16; implemented by the Tech Lead session, not by a separate agent
priority: P1
phase: 5
domain: tenancy            # cross-cutting: every owner page (docs/owner-portal.md)
requirements: []           # presentation only; every page keeps its contract in docs/owner-portal.md §8
invariants: []
lane: heavy                # touches every owner view and the shared blocks of owner-portal.md §9
approval: tech-lead
decision:
launch-blocker: false
depends-on: [BQ-160]
---

## Problem

The owner portal works (BQ-126, BQ-160), but it has no visual design. Every page is white panels on a
near-white background with grey text, so nothing tells the owner where to look first. The audit below was
made on 2026-11-16 against `integration` at `f28a680`, as Pemilik of the seeded business, at 1366 px and
375 px, on every page in the sidebar plus the booking panel, the walk-in dialog and the bottom navigation.

### Measured in the code (71 owner Blade files)

| What | Count | Why it matters |
|---|---|---|
| `<button>` elements | 177, written with **89 different class strings** | No button component; every page invents its own primary, secondary and danger button |
| Form controls (`input`, `select`, `textarea`) | 244, no shared field component | Heights, borders, focus rings and label styles differ per page |
| Border radii in use | `rounded-lg` 203, `rounded-xl` 110, `rounded` 54, `rounded-full` 63, `rounded-2xl` 10 | No radius scale, so nothing signals hierarchy |
| Type sizes | `text-sm` 498, `text-xs` 276, `text-base` 36, `text-lg` 16, `text-xl` 9, `text-2xl` 7 | Almost everything is 12–14 px; page titles, section titles and body text look alike |
| Raw Tailwind palette (`slate`, `gray`, `indigo`, …) | 32 | Bypasses the `bq-*` tokens |
| Shadows | seven different shadow utilities | Panels and overlays use the same elevation, so dialogs do not feel above the page |
| Icons | 19 uses of `x-owner.icon`, 48 inline SVGs | Two icon systems |

### The brand is not used

`resources/css/app.css` defines the official brand palette (`brand-purple #382186`, `brand-orange #FFB84D`,
`brand-lavender #B499FF`, `brand-ink #231A3D`, `brand-offwhite #F7F7FA`), and the logo is that purple. The
owner tokens (`bq-primary #4F46E5`) are Tailwind's default indigo instead. The owner sees a generic app, not
BookQu.

### Seen on screen

Shell
- S-01 The sidebar is white on a near-white page; active item is a faint indigo tint. Nothing anchors the layout.
- S-02 Phone bottom bar shows "Booking" twice (the centre "+ Booking" and the "Booking" list).
- S-03 Top bar: search field, "+ Booking", open-storefront icon, bell and account all have equal weight.
- S-04 Tabs overflow on phones without a hint ("Menung…" on Booking, Pengaturan hides "Tim").
- S-05 The page header has no visual weight; the title is a 24 px line followed by grey text.

Beranda
- B-01 The amber "10 layanan belum punya slot" banner and step 3 of the setup guide ask for the same thing twice.
- B-02 "Aksi cepat" is a column of four identical outline buttons; on desktop it sits in a mostly empty panel.
- B-03 Today's schedule, the one thing a cashier opens the app for, is not the first thing on the page.

Kalender
- K-01 On phones three full-width selects and the colour legend come before the first slot.
- K-02 "Sebelumnya / Berikutnya" are plain underlined links; the date is a sentence, not a control.
- K-03 Slot states use pale fills that are hard to tell apart; there is no "now" line on today.

Booking and the booking panel
- BK-01 On phones the filter block (search, two dates, service, source, sort, Cari, Reset, Ekspor) fills the first screen.
- BK-02 The table is dense grey text; booking codes are underlined links in one long column.
- BK-03 Panel footer stacks four full-width buttons, with "Batalkan booking" as a solid red block, so the destructive action is the loudest.
- BK-04 Panel shows raw method values ("kartu_kredit").
- BK-05 "Perlu tindakan (80)" is one long list; the reason chips are small grey text.

Pelanggan
- P-01 Three stat boxes and four filter cards look the same as the list cards below them.

Layanan, Unit, Jam Buka & Harga
- L-01 Service cards have a large empty image area when there is no photo.
- L-02 Unit page is a column of collapsed `<details>` rows with a "▶" marker; it reads like a raw HTML list.
- L-03 Jam Buka & Harga opens with ten identical warning boxes ("belum ada slot terbuka") before the tabs.

Promo, Ulasan
- PR-01 Promo cards are dense key–value text; the discount, the thing an owner scans for, is small.
- U-01 Ulasan rating bars are brown and the numbers sit far from their bars.

Pembayaran, Laporan
- PY-01 Saldo, sedang diproses and total dicairkan are three equal cards; the available balance is not the hero.
- LP-01 Laporan shows ten equal stat boxes in a grid; no comparison with the previous period, no chart on the overview.

Pengaturan, Paket & Tagihan, Notifikasi, Bantuan
- ST-01 Profil Bisnis is one long two-column form with no grouping and the save button at the bottom only.
- PK-01 Plan comparison lists Pro, Small, Medium in that order; the cards are long check-mark lists of equal weight.
- N-01 Notifikasi uses two rows of pill filters with the same style as primary actions.
- H-01 Bantuan is a plain accordion; the contact block looks like any other panel.

Environment (not part of this item)
- The local Laragon site intermittently answers 500 with `MissingAppKeyException` and env "production": the
  `.env` is fine, so PHP occasionally reads the environment empty under Apache. `php artisan config:cache`
  locally (cleared with `optimize:clear`) avoids it. Record separately if it appears on staging.

## Design direction

### Subject, audience, job

- **Subject:** BookQu, booking software for small Indonesian service businesses: photo studios, salons,
  clinics, courts, rental rooms.
- **Audience:** the owner and the cashier, often on a phone at the front desk, between customers; the owner
  also on a laptop for setup and money.
- **Primary job:** "What is happening today, and what do I need to do now?" Everything else (setup, reports,
  billing) is occasional.

### Concept: the front desk ledger ("meja depan")

A booking business runs on a day sheet: time down the side, who is coming, what is paid. The portal should
feel like a well-kept front-desk ledger in BookQu's own colours: calm paper surfaces, the brand aubergine as
the frame, and one warm marigold mark for "now" and "needs you". Information is structured as time and
status, not as a wall of identical cards.

### Tokens

Colour (base palette, all from the existing brand palette in `app.css`):

| Name | Hex | Role |
|---|---|---|
| Aubergine | `#382186` | Brand frame: sidebar background, primary buttons, active states, links |
| Aubergine deep | `#2D1A6D` | Hover/pressed for aubergine; sidebar active row |
| Marigold | `#FFB84D` | The one accent: the "now" line, "needs action" counts, the walk-in button ring. Never body text; text on marigold is Ink |
| Ink | `#231A3D` | Text and headings |
| Lilac mist | `#F3EFFE` (with `#E7E2F7` for borders on tinted areas) | Selected rows, active tab underlay, tinted section headers |
| Paper | `#F7F7FA` | App background; panels stay white `#FFFFFF` |

Status colours keep the existing AA pairs (`bq-success`, `bq-warning`, `bq-danger`, `bq-info` with their
`-bg`). Muted text stays `#666477` (AA on white and Paper). `bq-primary*` tokens are re-pointed to aubergine so
existing views change colour without edits; the raw indigo is removed.

Contrast (computed, WCAG 2.1): Aubergine on white 12.1:1; Aubergine on Lilac mist 10.8:1; white on
Aubergine deep 14.2:1; Ink on Marigold 9.5:1; Marigold on Aubergine 7.1:1; muted `#666477` on Paper 5.4:1;
sidebar group titles in `#E7E2F7` on Aubergine 9.6:1. Marigold on white is 1.7:1, so it is never text and
never the only focus indicator on light surfaces. Focus ring: 2 px Aubergine with a 2 px white offset on
light surfaces, 2 px Marigold on the aubergine sidebar.

Type: **Plus Jakarta Sans** (Tokotype, designed for Jakarta's city identity) replaces Inter for the whole
portal: one family, weights 400, 500, 600, 700. Numbers that line up (money, times, counts) use
`font-variant-numeric: tabular-nums`.

| Step | Size / line height | Weight | Use |
|---|---|---|---|
| Display | 30 / 36 | 700 | Hero figures only: today's count on Beranda, available balance |
| Title | 24 / 32 | 700 | Page title |
| Heading | 18 / 26 | 600 | Section / panel title |
| Body | 15 / 22 (16 on inputs below 768 px) | 400–500 | Default text |
| Small | 13 / 18 | 500 | Secondary lines, table meta, badges |

No all-caps labels, no tracked-out eyebrows. Sentence case everywhere.

Shape and depth:
- Radius scale: 8 px controls (buttons, inputs, badges get 999 px only when they are pills), 12 px panels,
  16 px dialogs and sheets. Nothing else.
- Panels use a 1 px border, no shadow. Only overlays (dialog, drawer, sheet, popover, toast) cast one shadow
  token. This makes "above the page" mean something.
- Spacing on a 4 px grid; page gutter 16 px phone, 24 px tablet, 32 px desktop; panel padding 20 px desktop, 16 px phone.

Motion: 150 ms ease-out for hover and press; 200 ms for drawers, dialogs and sheets sliding in; nothing moves
on page load. `prefers-reduced-motion` removes transforms.

### Layout

Desktop shell:

```text
+------------+-----------------------------------------------------------+
| BookQu     |  Cari booking atau pelanggan ...        [Walk-in] (bell) B |
| (aubergine)|-----------------------------------------------------------|
|            |  Booking                                   [+ Primary]    |
| Beranda    |  One line that says what this page is for.               |
| Kalender   |  ---- tabs ---------------------------------------------  |
| Booking  80|                                                           |
| Pelanggan  |  content, left aligned, max width 1200 px                 |
|  Bisnis    |                                                           |
| Layanan ...|                                                           |
+------------+-----------------------------------------------------------+
```

- Sidebar: aubergine background, white labels, group titles in `#E7E2F7` at 13 px, active item = deep aubergine
  row with a 3 px marigold bar on the left. Counts ("80") are marigold pills with ink text.
- Top bar: white, search takes the space, one filled action ("Walk-in"), icons quiet.
- Content is left aligned; wide tables and calendars may use the full width, forms stop at 720 px.

Phone shell:

```text
+-----------------------------+
| (bq)  Cari ...       (bell) |
|-----------------------------|
| content                     |
|                             |
|-----------------------------|
| Beranda Kalender (+) Booking Lainnya |
|                 marigold ring        |
+-----------------------------+
```

- Bottom bar: the centre button becomes a round aubergine "+" with a marigold ring and the label "Walk-in";
  the list keeps "Booking". Removes S-02.

Beranda (the one bold element lives here):

```text
Halo, Brama                                      Senin, 16 Nov
+--------------------------+  +----------------------------------+
| 12 booking hari ini      |  | Perlu tindakan              3    |
| 4 sudah datang · 1 lewat |  | Refund menunggu keputusan    1 > |
+--------------------------+  | Booking lewat belum ditandai 2 > |
                              +----------------------------------+
Hari ini
  08:00 |
  09:00 | [Umar W · Webinar room · Lunas]          [Tandai selesai]
--10:24-+------------------------- marigold "now" line ------------
  11:00 | [Maya W · Podcast suite · Menunggu bayar]
  12:00 |   kosong · [Catat walk-in]
```

- **Signature: the day rail.** Today's bookings on a vertical time rail with a marigold "now" line, the same
  component used by Kalender's day view. It is the only decorative-looking element in the portal; everything
  else stays quiet.
- Hero figure: today's booking count in Display size; one line of context under it. Not a row of stat cards.
- "Perlu tindakan" is a short list with counts that link to the filtered Booking tab.
- Setup guide stays until done but collapses to one line with progress ("2 dari 4 langkah") after the first
  visit; the "no open slot" banner is merged into its step 3 (fixes B-01).
- Quick actions move into the page header as one primary ("Walk-in") plus a "Tambah" menu (fixes B-02).

### Principles

1. **Time first.** Anything with a time is laid out on time (rail, grouped by day), not as cards.
2. **One loud thing per screen.** Aubergine fills only the primary action; marigold marks only "now" and
   "needs you". Destructive actions are outlined red until confirmed.
3. **Panels group, rows list.** A panel holds a set of rows; rows are not cards. Identical card grids only for
   things that are truly peers (services, promos).
4. **Phone at the counter.** Every daily task fits a 375 px screen with the primary action reachable by thumb;
   filters live in a sheet.
5. **Say it in the owner's words.** Labels from `BookingState::formatLabel` / `PaymentState::formatLabel`;
   never raw values like `kartu_kredit`.

### Review against the defaults

- The first draft put a gradient lavender hero band behind every page title. That is the generic SaaS look
  and adds nothing an owner needs, so page headers stay on Paper and get their weight from type size instead.
- The first draft used four stat cards on top of Beranda. Replaced by one hero figure plus the "Perlu
  tindakan" list, because a cashier needs "what now", not four numbers.
- A dark sidebar is common, but here it is the brand's own aubergine and the logo's colour, chosen because the
  current portal's problem is that nothing carries BookQu's identity.
- No numbered markers except the setup guide, which is a real sequence.

## Component system

New Blade components in `resources/views/components/ui/` (presentation only, no queries), documented in
`resources/views/components/owner/README.md` and in `docs/owner-portal.md` §9:

| Component | Variants / props | Replaces |
|---|---|---|
| `x-ui.button` | `primary` (aubergine), `secondary` (white, border), `ghost`, `danger` (outlined red), `danger-solid` (only inside a confirm), sizes `sm`/`md`, `icon`, `loading` | The 89 button class strings |
| `x-ui.field` | label, hint, error (wraps `x-owner.field-error`), required mark | Ad-hoc label/hint markup |
| `x-ui.input`, `x-ui.select`, `x-ui.textarea`, `x-ui.checkbox`, `x-ui.radio-card` | one height (40 px desktop, 44 px phone), one focus ring | 244 hand-styled controls |
| `x-ui.panel` | title, description, actions slot, `flush` for tables | `rounded-xl border bg-bq-surface p-5` copies |
| `x-ui.stat` | label, value, context line, optional trend; `hero` size | Equal stat boxes |
| `x-ui.badge` | status tones from `x-owner.status-badge`, dot + label | Coloured spans |
| `x-ui.table` | sticky header, row link, numeric columns right aligned, phone fallback to list rows | Per-page tables |
| `x-ui.list-row` | leading (time or avatar), title, meta, trailing (badge, amount, action) | Card-per-row lists |
| `x-ui.filter-bar` | inline on desktop, "Filter (n)" button opening a bottom sheet on phones | BK-01, K-01 |
| `x-ui.day-rail` | rows by hour, "now" line, empty-hour slot action | Beranda today list, Kalender day view |
| `x-ui.empty` | icon, one sentence, one action | `x-owner.empty-state` (restyled, same API) |
| `x-ui.skeleton` | line, block | "Memuat…" text in the booking panel |

The existing shared blocks keep their names and behaviour and are restyled: `x-owner.dialog`, `drawer`,
`tabs` (scrollable with edge fade, URL state unchanged), `toast`, `confirm-modal`, `page-header`,
`sidebar`, `topbar`, `bottom-nav`, `booking-panel`, `customer-panel`, `walkin-form`, `status-badge`, `icon`.
Inline SVG icons move to `x-owner.icon` (one 20 px stroke set).

## Page plan

Each page keeps its contract (contents, actions, acceptance criteria) in `docs/owner-portal.md` §8; only
layout and style change. "Fixes" refers to the audit IDs above.

| Page | What changes | Fixes |
|---|---|---|
| Shell | Aubergine sidebar with marigold active bar and count pills; top bar with one "Walk-in" action; phone bottom bar with "+ Walk-in" FAB; tabs scroll with edge fade; page header Title size + purpose line + one primary | S-01–S-05 |
| Beranda | Hero count, "Perlu tindakan" list, day rail for today, setup guide collapsed with the slot warning merged into step 3, link card compact, quick actions in the header menu | B-01–B-03 |
| Kalender | Date control as a segmented "‹ Hari ini ›" with a date picker; day view on `x-ui.day-rail` with now line; week/month with clearer state fills and a legend under the grid; filters in `x-ui.filter-bar` | K-01–K-03 |
| Booking | Filter bar/sheet; table on `x-ui.table` with code, customer, session, status badge, amount right aligned; phone list rows; "Perlu tindakan" grouped by reason with counts | BK-01, BK-02, BK-05 |
| Booking panel | Header like a booking slip: code large, status badge, session time; sections as rows; footer: one primary for the next step, secondary actions in a "Lainnya" menu, cancel as outlined danger; skeleton while loading; labelled payment method | BK-03, BK-04 |
| Walk-in dialog | Slot picker as time chips for the chosen date; payment method as `x-ui.radio-card`; sticky footer with total | — |
| Pelanggan | Stats as one row of `x-ui.stat`; segments as a segmented control, not cards; list rows with initials avatar, visits, total, last visit | P-01 |
| Layanan | Service cards with a neutral placeholder band (category colour from a fixed set) when no photo; price and duration as the card's main line; usage bar for the plan limit | L-01 |
| Unit | Units as rows with type, capacity, "dipakai oleh" and today's share as a small bar; edit in a drawer; conflicts as a warning panel with booking links | L-02 |
| Jam Buka & Harga | The ten warnings become one summary ("10 layanan belum punya slot 7 hari ke depan" + "Buka slot"); opening hours as a weekly grid; price simulator as a mini table | L-03 |
| Halaman Booking | Tabs unchanged; link card with large copy action; QR and poster side by side; landing editor with a sticky phone preview | — |
| Promo | Promo cards lead with the discount in Heading size and the code as a copyable chip; state badge; usage bar for quota | PR-01 |
| Ulasan | Rating summary with aubergine bars and counts at the bar end; review rows with stars, text, reply state | U-01 |
| Pembayaran | Available balance as the hero with "Cairkan" primary; processing and paid-out as secondary stats; transactions on `x-ui.table` | PY-01 |
| Laporan | Overview: four headline stats with change versus previous period (from the existing Laporan data, if not available leave the trend out), one chart, then the detail tabs | LP-01 |
| Pengaturan | Each tab as grouped panels (Identitas, Kontak, Alamat) with a sticky save bar that appears when the form is dirty | ST-01 |
| Paket & Tagihan | Current plan panel with usage bars; comparison ordered Small, Medium, Pro, the current plan marked; feature lists shortened to differences | PK-01 |
| Checkout, invoice | Same tokens and components; summary panel sticky on desktop | — |
| Notifikasi | Filters as a segmented control; unread rows with a lilac background and dot | N-01 |
| Bantuan | Contact panel with WhatsApp and email as clear actions; FAQ accordion restyled | H-01 |

## Plan

One branch, sections in this order, at least one commit per section (as BQ-160):

1. **Foundation:** tokens in `app.css` (re-point `bq-primary*` to aubergine, add marigold and lilac tokens, type
   scale, radius and shadow tokens), Plus Jakarta Sans in the owner layout (Google Fonts, weights 400–700),
   `theme-color` meta, focus ring, reduced motion.
2. **Components:** the `x-ui.*` set above with a component README; no page uses them yet.
3. **Shell:** sidebar, top bar, bottom bar, tabs, page header, dialog, drawer, sheet, toast, confirm.
4. **Daily pages:** Beranda (with `x-ui.day-rail`), Kalender, Booking, booking panel, walk-in, Pelanggan,
   customer panel.
5. **Catalog and setup:** Layanan, Unit, Jam Buka & Harga, Halaman Booking, Promo, Ulasan.
6. **Money and account:** Pembayaran, Laporan, Pengaturan (all tabs), Paket & Tagihan, checkout and invoice,
   Notifikasi, Bantuan.
7. **Sweep:** remove the raw palette uses, inline SVGs and unused classes; check every page at 375, 768 and
   1366 px; update `owner-portal.md` §9 and the component README.

## Rules for the implementer

- Presentation only: no change to controllers, Actions, routes, permissions, queries or data passed to views,
  except preparing a label the view needs (for example the payment method label) in the existing Action.
- Every page keeps its contract and acceptance criteria in `docs/owner-portal.md` §8. Moving an action into a
  menu is allowed; removing one is not.
- Copy stays as it is unless an audit ID above says otherwise; feature tests assert many labels. When a test
  asserts markup that the redesign replaces (a class, an element), update the assertion to the new markup and
  keep what it proves.
- WCAG AA contrast for text, visible focus on every interactive element, 44 px touch targets on phones,
  `prefers-reduced-motion` respected.
- No new npm or Composer dependency. Fonts come from Google Fonts like today.

## Invariants at risk

- None of the domain invariants: the change is presentation only. The guards are the existing feature tests
  for each page (`tests/Feature/Owner/*`), `OwnerSidebarNavigationTest` (menus filtered on the server) and
  `OwnerUiAuditTest`.

## Tests

- Full suite green; no test edited except markup assertions as described above.
- New: a small test per new component that renders it with each variant (`tests/Feature/Components/`).
- Manual: every owner page at 375, 768 and 1366 px as Pemilik and as Kasir, keyboard-only pass through the
  shell, booking panel and walk-in dialog; screenshots in the PR.

## Docs to update

- `docs/owner-portal.md` §9: the `x-ui.*` components and the token names (Tech Lead review).
- `resources/views/components/owner/README.md`: component usage.
- This item: evidence per section.

## Agent brief

```text
You are implementing BQ-161 (owner portal visual redesign) in the BookQu repository, in ONE branch
(agathan-bq-161-owner-ui-redesign) and ONE pull request, section by section as listed in the Plan.

Before coding: read AGENTS.md, docs/owner-portal.md (§3, §4, §8, §9), this work item completely,
.ai/rules/frontend.md, and the frontend-design and tailwindcss-development skills if your environment has them.

Work:
- Follow the Design direction exactly: tokens, Plus Jakarta Sans, radius and shadow scale, the x-ui components,
  the shell, and the page plan. Where the plan says "if the data is not available, leave it out", do so; never
  add queries or business logic to Blade or JavaScript.
- Build section 1 and 2 first and render every component variant once on a local page before restyling pages.
- Keep every contract action and acceptance criterion; keep copy unless an audit ID says otherwise.
- After each section: php artisan test --compact (or vendor/bin/phpunit), npm run build, and a browser check
  of the pages you touched at 375 px and 1366 px. Commit "style(owner): <summary> (BQ-161 §<n>)".
- If a test fails because it asserted replaced markup, update that assertion to the new markup and keep what it
  proves; if it fails for any other reason, fix the code.

Finish: vendor/bin/pint --dirty --format agent, vendor/bin/phpstan analyse --memory-limit=1G, full suite,
npm run build, screenshots of every page at 375 px and 1366 px in the PR, Evidence table in this item filled
per section, docs per "Docs to update". Push the branch and open ONE PR into integration titled
"style(owner): visual design system for the owner portal (BQ-161)", no attribution lines. Report what was
done per section, what was skipped and why, and the PR link.
```

## Evidence (fill when done)

| Section | Done | Skipped (why) | Tests | Manual checks |
|---|---|---|---|---|
| §1 Foundation `b9c85dd` | Brand tokens (`bq-primary` → aubergine, marigold `bq-accent`, Paper, borders), `font-owner` Plus Jakarta Sans 400–700, `bq-owner` base (focus ring, selection, label style, reduced motion), one overlay shadow `shadow-bq` | Type sizes stay on Tailwind's steps (body 14 px, not 15) so shared landing CSS is untouched | — | Build passes |
| §2 Components `b9c85dd` | `x-ui.button`, `field`, `input`, `select`, `textarea`, `panel`, `stat`, `badge`, `list-row`, `filter-bar`, `avatar`, `skeleton`; `bq-*` classes; 18 new `x-owner.icon` names | `x-ui.table` and `x-ui.day-rail` were not made components: tables keep their markup with the shared classes, the day rail lives in Beranda and the Kalender day view | `tests/Feature/Components/UiComponentsTest.php` (8 tests) | — |
| §3 Shell `b9c85dd` | Aubergine sidebar with marigold active bar and count pills; top bar with one "Walk-in" action, quieter search, marigold unread count; phone bar with the "+ Walk-in" FAB (S-02, contract §4.2/§4.3 wording updated); dialog, drawer, confirm, toast, tabs (edge fade, active tab scrolled into view), page header, status badge, empty state | — | `OwnerAppShellTest` regex updated for the "Walk-in" label | Desktop |
| §4 Daily pages `228ba92` | Beranda: four figures in one panel with the first as hero, day rail with marigold "now" line, "Perlu tindakan" with total, setup guide with progress bar and next step; Kalender: segmented date control, legend dots, state fills with strong left edge, today outlined; Booking: filter bar with "Filter (n)" on phones, table and phone rows, reason badges; booking panel: slip header, sectioned body, history line, skeleton, one primary action, outlined cancel, labelled payment method (`App\Support\PaymentMethodLabel`, BK-04); Pelanggan: stat strip, segmented filters, avatars | Beranda quick actions stay a panel (not a header menu); the "no open slot" banner is still separate from guide step 3 (it comes from `GetBeranda`); walk-in slot chips not built | `PaymentMethodLabelTest`; owner feature tests | Desktop: Beranda, Booking, panel, Kalender day and week |
| §5 Catalog `0c8ff48` | Layanan placeholder band and marigold "Populer"; Unit rows with icon, service count, today's usage bar, outlined delete; Jam Buka & Harga runway as one summary (L-03); Promo discount as the lead figure and dashed code chip; Ulasan aubergine bars | Opening hours weekly grid, Halaman Booking sticky preview, simulator mini table not built | Related feature tests | Desktop (before the pane was hidden) |
| §6 Money and account `0c8ff48` | Pembayaran available balance as an aubergine hero; Laporan stat grids as one divided panel; Paket & Tagihan plans ordered by price (Small, Medium, Pro); Profil Bisnis grouped into Identitas and Kontak with a sticky save bar; Notifikasi segmented chips; Bantuan contact as an aubergine panel | Laporan period comparison and overview chart (data not prepared by the Action); other settings tabs only got the shared classes | `PaketNotifikasiBantuanTest`, `SettingsTest`, `LaporanPageTest` | — |
| §7 Sweep | 86 inputs and 71 buttons moved to `bq-input`/`bq-btn` by script, class variables in 11 views, 142 repeated fragments (badges, panels, links, hover, shadows, `bq-num`), raw palette and hex colours replaced except the printable poster and the landing preview | 44 inline SVGs remain (icons inside buttons and charts) | Full suite | Every owner page (30 URLs) answers 200 with the new styles as Pemilik |

Measured after the change (83 owner and `ui` Blade files): 100 uses of `bq-btn-*`, 103 of `bq-input`; buttons that are not on the shared classes are menu rows in the sidebar, top bar and sheet. Raw palette uses 32 → 8 and hex colours → 7, all in the printable poster and the landing preview, which render the business's own colours.

Verification on 2026-11-16: `vendor/bin/phpunit` 982 tests, 979 pass; the 3 errors need the GD extension the local PHP CLI lacks (CI has it). PHPStan: no errors. Pint: passed. `npm run build`: passed. Screenshots at 1366 px were checked during the work; the browser pane was hidden for the last pages, so the phone pass (375 px) and the Kasir pass are left for the PR review.

Follow-up (not in this PR): the items in the "Skipped" column above.
