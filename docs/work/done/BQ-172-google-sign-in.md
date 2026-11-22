---
id: BQ-172
title: Sign in and register with Google through Laravel Socialite
type: feature
status: done
priority: P2
phase: 8
domain: auth
requirements: [FR-AUTH-001, FR-AUTH-002, FR-AUTH-004, FR-AUTH-005, FR-AUTH-006, FR-AUTH-007, FR-AUTH-008, FR-AUTH-009]
invariants: [INV-TEN-01]
lane: heavy
approval: product-lead
decision: D-029
launch-blocker: false
depends-on: []
---

## Problem

Owners and team members can sign in only with email and password (`Auth/AuthenticatedSessionController::store`, `Auth::attempt`). Registration (`Auth/RegisteredUserController` → `Actions/Owner/RegisterOwner`) requires a password and sends a verification mail. D-029 adds Google as a second way to sign in and register (FR-AUTH-009).

Current constraints found in code:

- `users.password` is `NOT NULL` (`0001_01_01_000000_create_users_table.php`), and no column stores a provider identity.
- Registration always creates a tenant and requires `nomorhp`, `nama_bisnis`, `jenis_bisnis`, `alamat` and `terms` (`RegisterRequest`). Google does not provide these fields.
- Email change, account deletion and payout account changes require the current password (BQ-098, BQ-170, D-004).
- Google Calendar has its own OAuth flow (`Infrastructure/Integrations/GoogleCalendar.php`, `services.google_calendar`). It stays separate and is not moved to Socialite.

Approved by the Product Lead (D-029) and by the Tech Lead for the dependency, schema and auth change, 22 Nov 2026, confirmed through the developer.

## Plan

1. **Dependency and config.** `composer require laravel/socialite:^5.33` (supports `illuminate/support ^13.0`). Add `services.google` with `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` and `GOOGLE_REDIRECT_URI` (default `route('auth.google.callback')`). Add empty keys to `.env.example`. Request scopes `openid email profile` only; store no Google access or refresh token.
2. **Schema.** One migration: `users.google_id` (string, nullable, unique) and `users.password` nullable. `google_id` is not mass-assignable through any request.
3. **Routes** (`routes/web.php`, `guest` group, throttled like `login.store`):
   - `GET auth/google/redirect` → `auth.google.redirect` (optional `intent=login|register`, kept in the session);
   - `GET auth/google/callback` → `auth.google.callback`;
   - `GET|POST auth/google/lengkapi` → `auth.google.complete` (business data for a new owner).
   Socialite's state check stays on (no `stateless()`). Routes live on the main application host only, like the existing auth routes.
4. **Action** `App\Actions\Auth\ResolveGoogleSignIn` receives the Socialite user and returns one outcome:
   - email not verified by Google → rejected;
   - user found by `google_id` → sign in;
   - user found by email → link `google_id` once, then sign in; an account with `role = admin` is rejected and never linked;
   - no user → pending registration: name, email and Google id are kept in the session for 30 minutes.
   A Google id already linked to a different user is rejected. Every rejection shows one generic Indonesian message; nothing internal is shown or logged with tokens (AGENTS.md rule 12).
5. **Shared sign-in finish.** Extract the post-login steps of `AuthenticatedSessionController::store` (session regenerate, forget `current_tenant_id`, verification redirect, role redirect, `intended`) into one place used by both password and Google sign-in, so the role redirect is not duplicated. Team membership checks stay in `TenantMiddleware`/`TenantContext`; a revoked membership behaves exactly as with password sign-in.
6. **Registration completion.** `auth.google.complete` shows the registration form without email and password fields (email shown read-only from the pending session). A Form Request validates `nomorhp`, `nama_bisnis`, `jenis_bisnis`, `alamat`, `terms`, and re-checks email uniqueness. `RegisterOwner` gains a path that creates the user with `google_id`, `password = null` and `email_verified_at = now()` in the same transaction as tenant and trial (BQ-112). No verification mail is sent. Expired or missing pending data returns to the login page.
7. **Accounts without a password.**
   - Password sign-in for such an account fails with the normal "Email atau password salah." message.
   - Account settings show "Kirim link buat kata sandi", which sends the FR-AUTH-007 reset link and signs the owner out (the reset page is guest-only); the email field is read-only and the deletion form is hidden. Every `current_password` check (account, deletion, payout accounts) fails while `password` is null, so the server rule does not rely on the hidden form.
   - Password reset works for an account whose password is null.
8. **UI.** "Masuk dengan Google" on `auth.login` and "Daftar dengan Google" on `auth.register`, following the current auth page style and Google's button guidelines. Account settings show "Terhubung dengan Google" when `google_id` is set. Unlinking is out of scope.
9. **Out of scope.** Accepting a team invitation through Google (an invitee still creates the account through BQ-146, then may sign in with Google), admin sign-in with Google, customer accounts, other providers.

## Invariants at risk

- **INV-TEN-01 / FR-AUTH-005.** Google sign-in only authenticates a user; tenant context and roles still come from `TenantMiddleware`, `TenantContext` and `TenantPermission`. Admin accounts are never linked or signed in through Google.
- **Account takeover.** Linking by email happens only when Google reports `email_verified = true`; a Google id is bound to one user; the OAuth state check stays on.
- **Atomic registration (BQ-112).** User, tenant and trial are created in one transaction on the Google path too.
- **Current-password confirmation (BQ-098, BQ-170, D-004).** Not weakened: an account without a password cannot pass it until it sets one.

## Tests

Socialite is mocked with `Socialite::fake()` / a mocked driver; no request reaches Google.

- Redirect route sends the user to the Google driver; signed-in users cannot reach it (`guest`).
- Callback: existing `google_id` signs in and redirects by role; existing email with verified Google email links and signs in; unverified Google email is rejected without linking; admin email is rejected and not linked; Google id linked to another user is rejected; provider error or denied consent returns to login with the generic message.
- Callback for an unknown email stores pending data and redirects to `auth.google.complete`.
- Completion creates user, tenant and trial atomically, with `email_verified_at` set and `password` null; validation errors keep the pending data; expired pending data redirects to login; a duplicate email created meanwhile is rejected.
- Team member (Manajer, Kasir) signs in through Google and keeps only their permissions; revoked membership is signed out on the next owner request.
- Password sign-in fails for a Google-only account; account update, deletion and payout account changes are rejected while `password` is null; password reset sets a password for such an account.
- Rate limits apply to the Google routes.
- Existing suites: `Auth/AuthTest`, `Auth/AtomicOwnerRegistrationTest`, `Owner/AccountChangeRequiresCurrentPasswordTest`, `Owner/SettingsTest`; then the full suite and PHPStan.

Manual: real Google sign-in on local (`bookqu.test`) and staging with the configured redirect URIs.

## Docs to update

- `docs/domains/auth.md`: entry points, Google flow, accounts without a password, evidence.
- `docs/operations.md`: Google OAuth client, env keys, redirect URIs per environment.
- `.env.example`: empty Google keys.

## Evidence (fill when done)

22 Nov 2026, branch `agathan-bq-172-login-google`:

- `php artisan test --compact tests/Feature/Auth`: 39 passed, including the 8 tests of `Auth/GoogleSignInTest` (linking, unverified email, admin, email linked to another Google id, registration completion without password, missing pending data, password sign-in for a Google-only account, password link).
- Full suite: 1087 passed, 3 failed only because the PHP 8.5 CLI has no GD; `LayananPageTest` and `HalamanBookingPageTest` pass on PHP 8.4.25 (25 passed).
- PHPStan: no errors. Pint applied. Blade views compile (`view:cache`).
- Security re-check (22 Nov 2026): OAuth state on, session regenerated after sign-in, no token stored or logged, `google_id` hidden and not mass-assignable, Google-only accounts fail every password check, secret only in the ignored `.env`. One gap fixed: linking an unverified account now removes the earlier password, remember token and sessions (`test_linking_an_unverified_account_removes_the_earlier_password_and_sessions`). Auth tests: 41 passed; PHPStan clean.
- Manual sign-in with a real Google account on `http://127.0.0.1:8000` checked by the developer. Not done: staging; payout forms show no extra hint for accounts without a password (the server check still rejects).
