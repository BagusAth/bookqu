# Authentication & accounts

Owns owner registration, login, email verification, password reset, roles and account settings.

## Entry points

| Route | Controller |
|---|---|
| `login`, `login.store`, `logout` | `Auth/AuthenticatedSessionController` |
| `register`, `register.store` | `Auth/RegisteredUserController` → `Actions/Owner/RegisterOwner`: creates `User` (role `owner`), `Tenant`, and a 7-day trial (`CreateTrialSubscription`) in one transaction (BQ-112); login and the verification mail follow the commit |
| `auth.google.redirect`, `auth.google.callback`, `auth.google.complete(.store)` | `Auth/GoogleAuthController` → `Actions/Auth/ResolveGoogleSignIn`, `RegisterOwnerWithGoogle` (BQ-172) |
| `owner.settings.account.password-link` | `Owner/OwnerPasswordSetupController`: first password for an account created through Google |
| `verification.notice/verify/send` | `Auth/EmailVerificationController` (`signed`, `throttle:6,1`) |
| `password.request/email/reset/store/update` | `Auth/PasswordResetLinkController`, `Auth/NewPasswordController` |
| `owner.settings.account`, `owner.settings.account.delete` | `Owner/OwnerSettingController` → `Actions/Owner/UpdateOwnerAccount`, `DeleteOwnerAccount` |

Middleware: `auth`, `verified`, `role:owner` / `role:admin` (`RoleMiddleware`). `User` implements `MustVerifyEmail`; password cast `hashed`.

## Requirements

FR-AUTH-001…006; FR-AUTH-007 (password reset) and FR-AUTH-008 (rate limiting) are `Proposed`; FR-AUTH-009 (Google sign-in, BQ-172).

## Evidence

`tests/Feature/Auth/AuthTest.php`, `Auth/PasswordResetTest.php`, `Owner/SettingsTest.php`, `Owner/AccountChangeRequiresCurrentPasswordTest.php`, `Auth/AtomicOwnerRegistrationTest.php`, `Auth/GoogleSignInTest.php`.

BQ-112: every owner page includes `components/owner/session-expired`, which wraps `window.fetch`; a same-origin response with status 419 or 401 opens the "Sesi Anda telah berakhir" dialog (reload or sign in again) instead of leaving only a generic failure toast.

BQ-098: changing the email or setting a new password requires `current_password` (`UpdateOwnerAccountRequest`); name and phone stay free. A new email clears `email_verified_at`, sends verification to the new address and `AccountEmailChangedNotification` (masked new address) to the old one. A new password rotates the remember token and deletes the user's other rows in the `sessions` table (database session driver), then regenerates the current session. Account deletion requires `delete_password` (`DeleteOwnerAccountRequest`) besides the typed confirmation.

## Status

Functional: Implemented · Verification: Verified (partial) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| None open | — |

BQ-131: platform owner role is distinct from the tenant role. TenantPermission owns the Pemilik/Manajer/Kasir map; permission middleware and policies enforce it server-side. Own account fields refer to the signed-in member, not the business owner. Cashier dashboard/customer responses omit revenue/spending, and notification delivery/feed/counts are role-filtered. Existing admin/customer authorization mechanisms are unchanged.

BQ-146: a team invitation expires after 24 hours. The emailed fragment is exchanged via a rate-limited, CSRF-protected POST for a session-bound invitation reference. Storage contains only the selector and secret hash. Delivery uses an encrypted queue job and a non-logging mailer. Acceptance rechecks expiry, invited email, membership and seats before consuming the invitation. New invitees create an account without opening a business or starting a trial.

BQ-173: the password reset and email verification links are built from `APP_URL` (`AppServiceProvider::buildMailedAuthLinksFromAppUrl`, the verification link signed on that origin), never from the request's Host header, so a forged Host cannot mail a reset token to another host. Evidence: `Auth/EmailLinkHostTest`.

BQ-172 (FR-AUTH-009, D-029): Google sign-in through Laravel Socialite, scopes `openid profile email`, no Google token stored. `ResolveGoogleSignIn` signs in by `users.google_id`, otherwise links the account with the same email once, only when Google reports `email_verified`; admin accounts and an email already linked to another Google id are rejected with one generic message. Linking an account whose email was never verified removes its password, rotates the remember token and deletes its sessions, because its creator never proved the email (pre-account takeover). An unknown Google account is kept in the session for 30 minutes (`google_registration`) while the owner completes the registration form without email and password (`CompleteGoogleRegistrationRequest`); `RegisterOwner` then creates user, tenant and trial in one transaction with `password = null` and the email verified, and no verification mail is sent. Password and Google sign-in share `Controllers/Concerns/FinishesSignIn`. `User::hasPassword()` is false for such an account: password sign-in fails, every `current_password` check fails, the account settings hide the password and deletion forms and make the email read-only, and the owner requests the FR-AUTH-007 reset link, which signs them out. Evidence: `Auth/GoogleSignInTest`.

BQ-170: the account update, account deletion and payout account mutations share a current-password budget of five requests per minute per signed-in user. Payout account mutations retain their stricter throttle. Validation never flashes the deletion password back into the session. The Google Calendar OAuth callback has a separate throttle, and a role gate with no configured roles denies access.
A revoked or otherwise invalid existing membership invalidates the session on the next owner request.
