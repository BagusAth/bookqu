---
id: BQ-092
title: Owner UI foundations - one palette, shared components, no decorative noise
type: improvement
status: rejected
priority: P1
phase: 8
domain: insights
requirements: [NFR-MAINT-003, NFR-UX-001, NFR-UX-005]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: []
---

# BQ-092: Owner UI foundations - one palette, shared components, no decorative noise

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-129](BQ-129-owner-shared-building-blocks.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-129.


Found in the owner UI audit of 2026-11-02. This item is the base for the page items (BQ-091, BQ-094 to BQ-097); do it first, in several small PRs.

## Problem

**Two design systems.** The sidebar, topbar, calendar, categories, notifications and modals use the brand purple palette as hard-coded hex (`#382186` 227 times, `#231a3d` 264 times) although `brand-*` tokens exist; dashboard, bookings, services, schedule, settings and the trial banner use indigo (`bq-primary` `#4F46E5`, 404 uses, plus 165 `indigo-*`). `theme-color` is `#4F46E5`, the chart is indigo, the sidebar active state purple. The same page mixes both (bookings table indigo, its detail modal purple). Source: `resources/css/app.css:8-50`.

**Same thing, different look.**
- Booking status badges: `paid` is emerald in some places and indigo in others; `completed` is emerald, sky or indigo; labels are English ("Confirmed", "Pending") and differ from `BookingState::formatLabel`. At least 8 local `match` blocks build them (dashboard, bookings table, mobile cards, detail modal, calendar views, schedule).
- Payment status: bookings table maps `expired`, a value payments never have, and shows `gagal` as "Unpaid" (`resources/views/owner/partials/bookings/desktop-table.blade.php:104-118`).
- Page headers: some pages use `components/owner/page-header`, others hand-write `<h1>` with different sizes and weights.
- Flash messages appear twice: as a toast (`components/owner/toast.blade.php:16-20`) and again inline on balance, bookings, categories, customers, programs and settings; the layout also prints `session('pesan')` inline while the toast shows it as a warning (`layouts/owner-layout.blade.php:53-60`).
- Confirmation: most deletes use `confirm-modal`, notifications use native `confirm()` (`resources/views/owner/notifications.blade.php:234`), program modals and settings use native `alert/confirm`.
- `confirm-modal` limitations: hardcodes a red trash-can icon and rose "Ya, Hapus" button (`components/owner/confirm-modal.blade.php:25-45`), making non-destructive confirmation look like a deletion; lacks `role="dialog"`, `aria-modal="true"`, focus trap, and return-focus (Added 2026-11-02 deep audit).
- Toast ids use `Date.now()`, so two toasts in the same millisecond share a key; no `aria-live`; close button without label.
- Stat cards: `components/owner/stat-card` plus a hand-copied "customers" card on the dashboard.

**Decorative noise (the "AI slop" the owner notices).** Uppercase tracked micro-labels (136 `uppercase`, many at 9–10 px), gradient washes on cards, buttons, avatar, plan card and progress bars (23), `backdrop-blur` on 37 elements, `transition-all` on 129, hover lift/scale on cards and icons (25), infinite `animate-ping`/`animate-pulse` dots, emoji inside menus and labels ("✓ Konfirmasi Lunas", "📅 Ubah Jadwal", "🔒 Terkunci"), arrows appended to links ("View All →"), four simultaneous active-state signals in the sidebar (background, left bar, bold, shadow), monospace for times, middle-dot meta strings.

## Plan

1. **Tokens.** Pick one brand palette (brand purple is the logo's; indigo is the default-template colour) and map it to semantic tokens: primary, primary-hover, ink, muted, surface, border, success, warning, danger, info. Replace hard-coded hex and `indigo-*` in owner views with tokens; set `theme-color` and chart colours from the same values.
2. **Type scale.** Body 14 px, small 12 px minimum; no text below 12 px; sentence case for labels; one weight for headings. Times in tabular figures, not monospace.
3. **Shared Blade components** (search `resources/views/components/` first, extend `components/owner/*`):
   - `status-badge` for booking and payment status, labels from one source (`BookingState::formatLabel` / `PaymentState::formatLabel`, shortened with the Tech Lead if needed), one colour per status;
   - `page-header` used by every owner page;
   - one flash mechanism (toast only, with `aria-live`; remove inline duplicates and the layout `pesan` block);
   - `confirm-modal` for every destructive action (remove native `confirm/alert`);
   - `empty-state`, `stat-card` (one), `card`, `button` variants, `icon` (SVG sprite or component, also for BQ-090).
4. **Restraint rules** written at the top of `resources/views/components/owner/` or in `.ai/rules/frontend.md` (Tech Lead approves rule changes): no infinite animations, no gradients as decoration, no hover lift on cards, no emoji in UI, no uppercase micro-labels, one active-state signal, motion only in answer to an action, respect `prefers-reduced-motion`.
5. Migrate page by page through BQ-091 and BQ-094 to BQ-097; this item delivers tokens, components and rules plus the shell (layout, toast, confirm, page header).

## Invariants at risk

None. Status labels must stay consistent with the domain formatters (`.ai/rules/frontend.md`).

## Tests

- Component tests (Blade `view()` rendering) for `status-badge` per status and for toast escaping.
- Visual check of every owner page at 375 px and 1440 px after each migration PR.

## Docs to update

`.ai/rules/frontend.md` (restraint rules, if the Tech Lead accepts them).

## Evidence (fill when done)

_Fill in when done._
