# Customer access (management links)

Customers authorize private booking/payment operations through the opaque grants defined in [ADR-0006](../adr/ADR-0006-management-link-module.md). Public order/booking references and all legacy tokens grant no access.

## Entry points

| Route | Authorization |
|---|---|
| GET /access | Minimal canonical first-party fragment bootstrap; no customer data |
| POST /access | CSRF-protected credential exchange; stores only grant ID and timeout metadata |
| Storefront payment, status, callback, pending-payment cancel, invoice | Exact payment Checkout or Management grant |
| /manage/payment/{order_id} and its invoice | Management grant for the exact payment and member snapshot |
| /manage/{booking_code}, cancel, reschedule, slots, invoice, review | Management grant covering that exact booking |

Application operations live in `app/Actions/CustomerAccess`; profiles, operation matrix and expiry live in `app/Domain/CustomerAccess`. The implemented profiles are Checkout and Management. No narrow-profile caller is needed yet.

## Authorization and delivery

- Grants persist a public selector and SHA-256 verifier of a random 256-bit secret, encrypted immutable recipient, exact member IDs, fixed expiry and family version. No recoverable bearer credential is stored.
- Each request resolves current grant, expiry, revocation, family and membership. Sessions expire after 30 minutes idle or 12 hours absolute, bounded by grant expiry; reopening the original valid link can establish a new session.
- TenantMiddleware consumes the verified context, rejects a conflicting storefront tenant and clears it afterwards. Private routes reject custom-domain origins; checkout hands off to the configured platform origin. Production cookies are host-only, Secure, HttpOnly and SameSite=Lax.
- Mutations revalidate grants inside their transaction under `ClaimSlots::lockTenant`. Pending-payment cancel delegates to PaymentLifecycle; status/callback delegate to PaymentOutcomeIntake. GETs do not query providers or issue credentials.
- Confirmation/reschedule flows persist delivery intent with recipient, members, generation and any source grant. After commit, an ID-only job synchronously mints immediately before sending; no mail transport runs under the tenant lock. Attempts are bounded at three, stale/revoked sources fail closed, and retries do not revoke earlier links. BQ-013/BQ-020 own later background execution.
- Legacy getters/issuers are removed. Retained legacy columns are hidden from serialization and never authorize access. Diagnostic issuance is restricted to local/testing.
- Invalid private access is generic 404 with rate limits. Private responses use no-store, no-referrer and noindex. HTML, invoice, Calendar/WhatsApp and provider return URLs contain no customer credentials or broader grants.

## Invariants

- **INV-TOK-01** — one grant authorizes only its exact subject/member snapshot and explicit profile operations. HTML and navigation never widen its authority.
- **INV-TEN-01** — foreign tenants/payments and inconsistent member sets are refused.

## Evidence and status

`CustomerAccessTest` covers every storefront/management entry, profile separation, tenant/payment mismatch, expiry/revocation, fixed lifetime, CSRF, session timeouts, safe HTML/share output and delivery retries. `CustomerAccessLockingTest` proves tenant-lock ordering on MySQL. Existing BookingFlow, BookingManage, BookingReview, PaymentGroupManagement, P0Security and RF09 regressions use real new grants.

Functional: Implemented for the BQ-001 scope · Verification: automated HTTP/HTML and MySQL locking verified · Architecture: module implemented; remaining ADR-0006 features below.

## Remaining work

| Gap | Tracking |
|---|---|
| Expired-link recovery, owner resend/security replacement UI and per-family rate limits | Explicitly deferred by the lead's BQ-001 scope; see its Evidence |
| Background delivery and operations verification | BQ-013, BQ-020 |
| Drop legacy credential columns | Later contract migration after cutover |
| Customer reschedule availability query still needs architectural alignment | BQ-012 follow-up / Phase 6 |

See [BQ-001 Evidence](../work/done/BQ-001-order-id-exposes-management-links.md), [BQ-052](../work/done/BQ-052-audit-t5-01.md) and [BQ-053](../work/done/BQ-053-audit-t5-02.md).