# Authentication & accounts

Owns owner registration, login, email verification, password reset, roles and account settings.

## Entry points

| Route | Controller |
|---|---|
| `login`, `login.store`, `logout` | `Auth/AuthenticatedSessionController` |
| `register`, `register.store` | `Auth/RegisteredUserController` — creates `User` (role `owner`), `Tenant`, and a 7-day trial (`CreateTrialSubscription`) |
| `verification.notice/verify/send` | `Auth/EmailVerificationController` (`signed`, `throttle:6,1`) |
| `password.request/email/reset/store/update` | `Auth/PasswordResetLinkController`, `Auth/NewPasswordController` |
| `owner.settings.account`, `owner.settings.account.delete` | `Owner/OwnerSettingController` → `Actions/Owner/UpdateOwnerAccount`, `DeleteOwnerAccount` |

Middleware: `auth`, `verified`, `role:owner` / `role:admin` (`RoleMiddleware`). `User` implements `MustVerifyEmail`; password cast `hashed`.

## Requirements

FR-AUTH-001…006; FR-AUTH-007 (password reset) and FR-AUTH-008 (rate limiting) are `Proposed`.

## Evidence

`tests/Feature/Auth/AuthTest.php`, `Auth/PasswordResetTest.php`, `Owner/SettingsTest.php`.

## Status

Functional: Implemented · Verification: Verified (partial) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Login and registration have no rate limiting | BQ-007 |
| Changing email or password needs no current password; email change keeps verification; other sessions stay logged in | BQ-098 |
| Audit P0: dead "forgot password" link, redirect loop, logout without CSRF handling on verify notice (status unknown since `ab24193`) | BQ-040 … BQ-042 |
| Owner registration not wrapped in DB transaction, and silent asynchronous CSRF 419 session expiration | BQ-112 |
