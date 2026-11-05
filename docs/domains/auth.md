# Authentication & accounts

Owns owner registration, login, email verification, password reset, roles and account settings.

## Entry points

| Route | Controller |
|---|---|
| `login`, `login.store`, `logout` | `Auth/AuthenticatedSessionController` |
| `register`, `register.store` | `Auth/RegisteredUserController` → `Actions/Owner/RegisterOwner`: creates `User` (role `owner`), `Tenant`, and a 7-day trial (`CreateTrialSubscription`) in one transaction (BQ-112); login and the verification mail follow the commit |
| `verification.notice/verify/send` | `Auth/EmailVerificationController` (`signed`, `throttle:6,1`) |
| `password.request/email/reset/store/update` | `Auth/PasswordResetLinkController`, `Auth/NewPasswordController` |
| `owner.settings.account`, `owner.settings.account.delete` | `Owner/OwnerSettingController` → `Actions/Owner/UpdateOwnerAccount`, `DeleteOwnerAccount` |

Middleware: `auth`, `verified`, `role:owner` / `role:admin` (`RoleMiddleware`). `User` implements `MustVerifyEmail`; password cast `hashed`.

## Requirements

FR-AUTH-001…006; FR-AUTH-007 (password reset) and FR-AUTH-008 (rate limiting) are `Proposed`.

## Evidence

`tests/Feature/Auth/AuthTest.php`, `Auth/PasswordResetTest.php`, `Owner/SettingsTest.php`, `Owner/AccountChangeRequiresCurrentPasswordTest.php`, `Auth/AtomicOwnerRegistrationTest.php`.

BQ-112: every owner page includes `components/owner/session-expired`, which wraps `window.fetch`; a same-origin response with status 419 or 401 opens the "Sesi Anda telah berakhir" dialog (reload or sign in again) instead of leaving only a generic failure toast.

BQ-098: changing the email or setting a new password requires `current_password` (`UpdateOwnerAccountRequest`); name and phone stay free. A new email clears `email_verified_at`, sends verification to the new address and `AccountEmailChangedNotification` (masked new address) to the old one. A new password rotates the remember token and deletes the user's other rows in the `sessions` table (database session driver), then regenerates the current session. Account deletion requires `delete_password` (`DeleteOwnerAccountRequest`) besides the typed confirmation.

## Status

Functional: Implemented · Verification: Verified (partial) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Login and registration have no rate limiting | BQ-007 |
| The current-password check on account changes and deletion has no rate limit of its own (`routes/web.php` is reserved for BQ-128/BQ-130) | FR-AUTH-008 |
| Audit P0: dead "forgot password" link, redirect loop, logout without CSRF handling on verify notice (status unknown since `ab24193`) | BQ-040 … BQ-042 |

BQ-131: platform owner role is distinct from the tenant role. TenantPermission owns the Pemilik/Manajer/Kasir map; permission middleware and policies enforce it server-side. Own account fields refer to the signed-in member, not the business owner. Cashier dashboard/customer responses omit revenue/spending, and notification delivery/feed/counts are role-filtered. Existing admin/customer authorization mechanisms are unchanged.

BQ-146: a team invitation expires after 24 hours. The emailed fragment is exchanged via a rate-limited, CSRF-protected POST for a session-bound invitation reference. Storage contains only the selector and secret hash. Delivery uses an encrypted queue job and a non-logging mailer. Acceptance rechecks expiry, invited email, membership and seats before consuming the invitation. New invitees create an account without opening a business or starting a trial.
A revoked or otherwise invalid existing membership invalidates the session on the next owner request.
