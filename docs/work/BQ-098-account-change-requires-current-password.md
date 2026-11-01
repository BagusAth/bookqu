---
id: BQ-098
title: Changing the owner's email or password does not ask for the current password
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: auth
requirements: [FR-AUTH-006, NFR-SEC-006]
invariants: []
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: []
---

# BQ-098: Changing the owner's email or password does not ask for the current password

Found in the owner UI audit of 2026-11-02 (Settings → Account).

## Problem

The account form changes name, email, phone and password in one request with no proof of the current password (`app/Http/Requests/Owner/UpdateOwnerAccountRequest.php:25-28`, `app/Actions/Owner/UpdateOwnerAccount.php:17-30`, form at `resources/views/owner/settings.blade.php:116-168`).

Anyone who gets a logged-in owner session (shared computer, stolen session cookie, XSS such as BQ-089) can change the email and password and lock the owner out of the business, its balance and its payouts. In addition:

- a new email is not re-verified (`email_verified_at` stays), and the old address is not told about the change;
- other sessions stay logged in after a password change.

Account deletion should be checked the same way (the form asks the owner to type the email in the browser; confirm the server requires the password).

## Plan

1. Require `current_password` (Laravel rule) for any change of email or password; keep name/phone edits free.
2. On email change: reset verification and send verification to the new address; notify the old address.
3. On password change: `Auth::logoutOtherDevices` (or session regeneration plus invalidating other sessions).
4. Server-side password confirmation for account deletion if it is missing.
5. Indonesian validation messages; the form shows the current-password field only when email or password changes.

## Invariants at risk

Login and verification flows (`docs/domains/auth.md`); owner access to tenant data must stay unchanged for valid sessions.

## Tests

- Email or password change without or with a wrong current password → validation error, nothing saved.
- Correct current password → saved; email change resets verification and queues the mails; password change ends other sessions.
- Name/phone change needs no password.

## Docs to update

`docs/domains/auth.md`.

## Evidence (fill when done)

_Fill in when done._
