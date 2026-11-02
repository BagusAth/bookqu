---
id: BQ-112
title: Atomic owner registration transaction and asynchronous CSRF expiration handling
type: bug-fix
status: approved
priority: P2
phase: 8
domain: auth
requirements: [FR-AUTH-001, FR-TENANT-001, NFR-REL-001, NFR-UX-005]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: []
---

# BQ-112: Atomic owner registration transaction and asynchronous CSRF expiration handling

Found during deep audit of owner authentication, onboarding, and asynchronous operations on 2026-11-02.

## Problem

### 1. Non-Atomic Owner Registration (NFR-REL-001)
In `app/Http/Controllers/Auth/RegisteredUserController.php:32-71`:
- The registration flow executes:
  1. `User::create(...)`
  2. Slug generation loop
  3. `Tenant::create(...)`
  4. `$createTrial->execute($tenant)`
- **These database mutations are not wrapped in a `DB::transaction()`.**
- If an unhandled exception or connection error occurs after `User::create` (e.g. during slug verification or trial subscription initialization), an orphaned user account is left behind without a corresponding tenant or subscription. When that user logs in subsequently, `EnsureOwnerProfileComplete` middleware and `TenantScope` enter an unresolvable redirect loop between `/owner/dashboard` and `/owner/settings`.

### 2. Silent Asynchronous CSRF (HTTP 419) Expiration
In Alpine asynchronous forms across owner drawers and modals:
- Customer CRM note save (`resources/views/owner/customers.blade.php:41-57`)
- Booking status update (`resources/views/owner/partials/calendar/detail-modal.blade.php:255`)
- Notification read and clear actions
When a browser session expires (e.g. left open on a reception tablet), subsequent asynchronous `fetch` requests receive `HTTP 419 (Page Expired)`. The client-side `.catch()` blocks display a generic failure toast ("Catatan gagal disimpan. Silakan coba lagi.") without alerting the user that their authentication session has expired, leading to repeated futile submit attempts.

## Plan

1. Wrap the registration pipeline in `RegisteredUserController::store` inside `DB::transaction()` to ensure atomicity across `User`, `Tenant`, and initial `Subscription` creation.
2. Implement global response interception or standardize fetch handlers in `layouts/owner-layout.blade.php`:
   - When a fetch request receives HTTP 419 or 401, display a dedicated modal or toast informing the owner that their session has expired and prompt for page refresh or re-login.

## Invariants at risk

None. Tenant association (`FR-TENANT-001`) and data integrity are strengthened.

## Tests

- Feature test: if trial creation throws an exception during registration, the entire transaction rolls back and no orphaned `User` record remains.
- Browser / fetch test: an asynchronous POST returning 419 triggers the session expiration alert.

## Docs to update

`docs/domains/auth.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
