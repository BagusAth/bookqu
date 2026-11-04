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

BQ-131: platform owner role is distinct from the tenant role. TenantPermission owns the Pemilik/Manajer/Kasir map; permission middleware and policies enforce it server-side. Own account fields refer to the signed-in member, not the business owner. Cashier dashboard/customer responses omit revenue/spending, and notification delivery/feed/counts are role-filtered. Existing admin/customer authorization mechanisms are unchanged.

BQ-146: a team invitation expires after 24 hours. The emailed fragment is exchanged via a rate-limited, CSRF-protected POST for a session-bound invitation reference. Storage contains only the selector and secret hash. Delivery uses an encrypted queue job and a non-logging mailer. Acceptance rechecks expiry, invited email, membership and seats before consuming the invitation. New invitees create an account without opening a business or starting a trial.
A revoked or otherwise invalid existing membership invalidates the session on the next owner request.
