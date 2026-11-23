---
id: BQ-084
title: Port the approved three-step booking schedule from new-ui
type: feature
status: done
priority: P1
phase: 4
domain: booking
requirements: [FR-BOOKING-002, FR-BOOKING-003, FR-BOOKING-004, FR-BOOKING-005, FR-BOOKING-016]
invariants: [INV-TEN-01, INV-SCH-01, INV-BOOK-03, INV-BOOK-06, INV-TOK-01]
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-083]
---

## Problem

Bagus's approved `origin/new-ui` UI combines date/time into an AJAX schedule page, but predates the payment/access modules and storefront hardening on `agathan`. Source commits: `92f7bb4`, `a8a9f44` (Bagus Athallah).

## Plan

Lead-approved brief, 2026-11-01: cherry-pick both commits with original authors/messages, preserve existing agathan behavior when resolving conflicts, then adapt in a separate commit. Shared schedule Action and date/slot validation; session-only service/date trust; throttled AJAX; Indonesian copy/24-hour WIB; safe Alpine serialization and pinned runtime. Redirect legacy GET date/time routes; preserve legacy POSTs.

## Invariants at risk

Availability delegates to GetAvailableSchedules/BookingRules. Tenant/service resolution stays scoped; final checkout still claims slots under locks. Retain BQ-083 fingerprints, access grants, pending-payment banners, no-store/bfcache behavior, validation and lifecycle intact.

## Tests

Three-step flow, date-window/blocked/quota refusals without session mutation, session date authority, AJAX validation/isolation, legacy redirects and safe rendering. Retain BQ-083 regressions; full suite, Pint, PHPStan, four CI jobs and local browser checks. Never call live providers.

## Docs to update

FR-BOOKING-002/003, booking domain, v1.0.0 release notes and this work item. PR remains open for Bagus review and lead manual acceptance; do not merge.

## Evidence (fill when done)

Implemented in isolated worktree `C:/laragon/www/bookqu-bq084` from `origin/agathan` at `d6f953a`. Vendor copied (not junctioned); reflection confirms the worktree controller path. Lead approved the detailed port/standards brief before implementation.

- Source cherry-picks: `8d8354e` (`92f7bb4`) and `71fb0a2` (`a8a9f44`), retaining Bagus Athallah's author/date and original messages. Conflicts in controller/checkout/time view retained agathan's existing methods/views, adding Bagus's new schedule methods/view/header/routes/tests; standards adaptation is a separate commit. Before formatting, 11 checkout/payment/access/restart/resolver methods matched the base verbatim. StorefrontCheckoutState, booking/payment Actions and gateway infrastructure are unchanged.
- Final flow: three Indonesian steps, 24-hour WIB; 31 dates via GetAvailableSchedules, one shared page/AJAX Action, one POST date validator (quota uses SubscriptionUsage), one shared slot path. Legacy GETs redirect while preserving flash; POSTs remain usable and ignore request date in select-time. AJAX uses session service only, JSON date validation, tenant/service isolation and throttle; browser requests abort/ignore stale responses and show retryable inline errors. Pinned Alpine configuration is serialized with @js.
- Kept the pending-payment banners, checkout fingerprint, no-store/bfcache safeguards and grant/lifecycle integration. The misleading multi-slot override note now checks actual per-slot overrides rather than comparing a multi-slot total with a single-slot price; pricing itself is unchanged.
- Deleted unused `date-selection.blade.php`, `time-selection.blade.php`, `booking-date.js`, `booking-time.js` after app/view/route/test reference checks. Legacy URL names remain. Date/time-specific GET/copy assertions moved to Jadwal; availability tests still prove counts of past/available slots through dates + AJAX; no checks of quotas, isolation, prices or bookings were removed. Touched time fixtures use travelTo.
- Verification: full SQLite suite **628 passed / 3,325 assertions**; focused booking/legacy regression suites pass. Production build, Pint and PHPStan pass (PHPStan with CLI opcache disabled as requested). Four CI jobs must also pass on the final PR head; this PR remains unmerged for manual acceptance. PHPStan baseline and architecture allowlist each shrink by one entry.
- Real Edge/Playwright with actual rendered HTML and synthetic SQLite fixtures: desktop + 360px mobile, AJAX date swap/error/retry, disabled closed date, two contiguous slots/total/unique inputs, gap refusal, pageshow reset and malicious quote/script text; zero Alpine runtime errors. Fixed a mobile grid overflow discovered by this check. All external browser requests blocked/replaced with local assets; checkout gateway mocked in feature tests. This is not a live-provider or production smoke test.
- PR stays open for Bagus review and lead manual acceptance; no merge/deploy and no change to `main` or `new-ui`. Residual: availability can change after AJAX (final locked checkout remains authoritative); BQ-083 checkout idempotency limitation is unchanged. The worktree and its node/build junctions are retained; detach junctions before any later cleanup.
