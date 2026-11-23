---
id: BQ-174
title: Deploy public assets to Laravel's public path and keep the storage link
type: ops
status: in-progress
priority: P0
phase: 5
domain: staged-modules
requirements: []
invariants: []
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: []
---

# BQ-174: Deploy public assets to Laravel's public path and keep the storage link

## Problem

After the first production deploy of the redesign (2026-11-23):

1. Customer booking pages answered 500. Production log: `filemtime(): stat failed for …/laravel/public/js/booking-schedule.js (View: …/customer/booking/schedule-selection.blade.php)`. Four views (`checkout`, `program-selection`, `schedule-selection`, `manage/reschedule`) call `filemtime(public_path('js/…'))`. `public_path()` is `laravel/public`, but `deploy-production.yml` copied `public/*` only to `public_html/`; only `build/` reached `laravel/public/`.
2. Uploaded images did not show. They are stored on the `public` disk (`storage/app/public`) and served at `/storage/…`, which needs the symlink `public_html/storage`. The production deploy never created it (`deploy-staging.yml` runs `storage:link`, which on HostData would point from `laravel/public`, not the web root).

## Plan

1. In `deploy-production.yml`, copy the static `public/` entries (except `index.php`, `.htaccess`, `.user.ini`, `build`, `storage`, `hot`) to both `public_html/` and `laravel/public/`.
2. Before the backup, create or repair `public_html/storage` → `laravel/storage/app/public`; stop the deploy if a real directory sits there (it may hold uploads that must be merged by hand).
3. After the swap, verify every `public/js/*.js` exists in both places and the symlink resolves to the public disk.
4. Document it in `docs/operations.md` §3.

## Invariants at risk

None in the domain. Deploy safety: the storage check runs before maintenance and before any file changes, so a failure leaves production untouched.

## Tests

- Workflow YAML parses; the remote script passes `bash -n`.
- Manual: next production deploy log shows the storage link and the asset checks pass; booking schedule, checkout and reschedule pages answer 200; an uploaded service image renders.

## Docs to update

- `docs/operations.md` §3

## Evidence (fill when done)
