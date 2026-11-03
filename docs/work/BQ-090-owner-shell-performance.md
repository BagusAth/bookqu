---
id: BQ-090
title: Make the owner sidebar, topbar and layout light
type: improvement
status: approved
priority: P1
phase: 8
domain: notifications
requirements: [NFR-PERF-001, NFR-PERF-004, NFR-MAINT-001]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: []
---

# BQ-090: Make the owner sidebar, topbar and layout light

Found in the owner UI audit of 2026-11-02 (browser measurements on the local demo tenant).

## Problem

The owner shell feels heavy because it does work on every page and on every mouse movement:

| Cause | Evidence | Effect |
|---|---|---|
| Hover prefetch: hovering a sidebar link for 65 ms injects `<link rel=prefetch>` for the full page | `resources/views/components/owner/sidebar.blade.php:615-635`; moving the mouse down five links fetched calendar, schedule, bookings, services and categories (0.5–1 s of server work each) | Server load multiplies; on shared hosting the real click waits behind prefetches |
| Notification bell initialised twice: `x-data="ownerNotificationBell()"` already calls `init()`, and `x-init="init()"` calls it again | `topbar.blade.php:165`, `:437-442`; two identical `/owner/notifications?limit=8` requests on load and every 30 s | Two intervals, double requests forever |
| Bell fetches the list on page load although the unread count is already rendered | `topbar.blade.php:432`, `:438` | One extra request per page view |
| Polling never pauses: bell every 30 s on every owner page, dashboard every 30 s | `topbar.blade.php:439`, `dashboard.blade.php:591-594` | Requests continue in background tabs |
| Queries in Blade on every page: tenant, subscription, unread count in the sidebar; tenant again and unread count again in the topbar | `sidebar.blade.php:6-12`, `topbar.blade.php:3-4`, `:432`; also an unused `adalahpro` composer (`app/Providers/AppServiceProvider.php:58-90`) | Repeated queries; violates `.ai/rules/frontend.md` |
| Chart.js (unpinned `@4`) loaded on every owner page | `resources/views/layouts/owner-layout.blade.php:21` | Only the dashboard draws a chart |
| Seven Inter weights (300–900) | `owner-layout.blade.php:18` | Larger font download than needed |
| Landing-page JS and CSS bundled into the owner app | `resources/js/app.js:3-8` | Dead code on every owner page |
| Continuous animations and blur: `animate-ping` on the bell badge, unread dots and a "business online" dot; `backdrop-blur` on the sticky header and dropdowns; `transition-all` and hover translate on every nav item | `topbar.blade.php:158, 195, 319`, `sidebar.blade.php:318-320`; 5 blurred layers and 2 infinite animations on the dashboard | Constant repaint and compositing while idle |
| Four scroll-persistence mechanisms and a wheel-forwarding hack for the sidebar | `sidebar.blade.php:488-613` | Extra listeners for a 14-item menu |
| Large HTML: dashboard 150 KB, bookings 420 KB, schedule 507 KB, calendar 478 KB | inline SVG per item, desktop and mobile markup both rendered, per-row JSON payloads | Slow parse on phones |

Note: locally every request, even `/up`, takes ~0.5 s (Laragon), so absolute timings must be re-measured on production; the waste above is independent of that.

## Plan

1. Remove hover prefetch.
2. Bell: initialise once; fetch the list only when the dropdown opens; poll the unread count only while the tab is visible (`document.visibilityState`), with a longer interval; same for the dashboard refresh.
3. One view composer (or a small owner-shell view model) provides tenant, plan summary and unread count to sidebar and topbar once per request; no queries in Blade. Plan summary comes from `EntitlementRules` (see BQ-086).
4. Load Chart.js only on pages that draw charts, pinned to an exact version (or bundled through Vite).
5. Keep only the Inter weights in use (likely 400/500/600/700).
6. Split Vite entries: `app.js` for the owner/customer apps, a separate entry for the landing page.
7. Remove infinite animations, header blur and hover translate on navigation; keep motion only where it answers an action (BQ-092 principles).
8. Replace the scroll scripts with nothing (sidebar fits) or one `scrollIntoView` of the active item.
9. Cut HTML weight: one SVG sprite or Blade icon component, render one layout per breakpoint where possible, lazy-load modal payloads.

## Invariants at risk

None. Behaviour visible to the owner stays the same except fewer background requests.

## Tests

- Feature: sidebar/topbar render with the composer data; no query count regression (assert query count on an owner page).
- Manual: browser network panel — one bell request on open, no prefetch on hover, polling stops in a hidden tab; Lighthouse before/after on dashboard, bookings and calendar.

## Docs to update

`docs/domains/notifications.md` if polling intervals are documented; none otherwise.

## Evidence (fill when done)

_Fill in when done._
