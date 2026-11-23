# ADR-0006: Management link module

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead); remaining policy choices delegated to the agent
> **Work item:** BQ-001 (module and access cutover), BQ-053 (scope regression), BQ-052 (sharing regression), BQ-002 (payer-cancel integration), BQ-028 (Phase 2 package)

## Context

Customers have no accounts. They need payment access before settlement and a usable management link afterwards. Public identifiers currently authorize storefront payment/invoice/status/cancel endpoints; invoices expose management credentials (BQ-001/BQ-002). Controller scope checks are defeated by HTML that emits another operation's token (BQ-053). The individual booking page still exports the presented management URL to Calendar and WhatsApp (`resources/views/customer/manage/show.blade.php:25-42`); the group page has removed it (BQ-052).

Issuing, resolving and constructing links are spread across models, controllers, Actions, mail and views. Raw tokens have no expiry/revocation policy. Existing tests preserve legacy cancellation-token view access, but do not establish that its incidental invoice/review access or HTML escalation is desired. The product has not launched to real customers, so a controlled incompatible credential cutover is available.

The lead explicitly accepted one issuing/resolving/link-building module and a separate checkout credential, then delegated all remaining choices for candidate #3. This ADR records those choices and the accepted requirement clarifications; it is a target, not evidence of implementation. Payment and booking eligibility remain with ADR-0004/ADR-0005 and their Actions.

## Options

1. **Separate operation links everywhere.** Restricts each secret, but makes normal email/navigation orchestration difficult and risks recreating the current HTML escalation. Retain these only as deliberately narrow profiles.
2. **Signed claims or a general permission/delegation system.** Flexible, but revocation and current ownership still require database checks. Extra claims, keys and arbitrary delegation add little value for two fixed subject types.
3. **One explicit Management grant with narrower profiles, stored as opaque hashed credentials.** Keeps one ordinary customer email link and makes its authority deliberate. Sessions remove secrets from subsequent URLs; fixed profiles prevent accidental widening. Chosen. Hash-only storage requires fresh issuance for later delivery, rather than reconstructing a secret from a model getter.

## Decision

### 1. Module and interface

The Management link module owns `issue`, `resolve`, `links` and `revoke`: trusted issuance, subject/capability validation, permitted link construction and revocation. Application operations live in `app/Actions/CustomerAccess`; profile/expiry rules in `app/Domain/CustomerAccess`. Use existing framework crypto, sessions and database support; add no auth package or customer account system.

Callers select a closed issuance profile and a server-resolved subject/reason, never arbitrary permissions, recipients, tenants, return URLs or expiry timestamps from HTTP input. Resolution returns a typed, request-scoped access context; link construction is a projection of that context and does not issue credentials. Booking eligibility, price, quota and mutations stay with their existing owners. Customer grants never authorize owner/admin or subscription-payment operations.

### 2. Explicit grants and capabilities

An access grant binds a tenant, an exact booking/payment group, a closed profile and explicit operations. Management is deliberately a bundle of customer operations; it is not a read credential that discovers other secrets. Narrow profiles cannot become Management by opening another page, downloading an invoice or invoking a callback.

| Operation | Management | Checkout | Invoice | Cancellation | Reschedule | Review |
|---|---|---|---|---|---|---|
| Booking details | Bound group/members | Purchased payment summary only | Invoice projection only | Own booking context | Own booking context | Own booking context |
| Payment view/status/continue | Yes | Yes | No | No | No | No |
| Cancel pending payment | Yes | Yes | No | No | No | No |
| Invoice after success | Yes | Yes, checkout invoice | Yes | No | No | No |
| Cancel eligible booking | Yes | No | No | Yes | No | No |
| Reschedule eligible booking | Yes | No | No | No | Yes | No |
| Review eligible booking | Yes | No | No | No | No | Yes |

Every permission also requires current Action eligibility. Continue/cancel-payment requires a pending, unexpired booking payment; success is established by ADR-0004, never browser payloads. Cancellation/reschedule/review target only an explicitly granted member, with tenant and payment membership verified. Existing customer multi-slot cancellation/reschedule refusals remain; this ADR adds no group mutation operation.

Ordinary confirmations deliver one Management link. Narrow operation profiles are available to trusted delivery callers, not a public arbitrary-link generator. Management/Checkout may explicitly request an Invoice-only link through a CSRF-protected POST; its child grant stays in the same family, narrows the subject/rights and expires no later than its parent. Only this one-level attenuation is supported. Other navigation/forms use the current authorized session, not separately fetched operation tokens.

### 3. Storage, subjects and revocation families

Add module-owned `customer_access_grants` and `customer_access_deliveries` records in new migrations. Grants store a unique public selector, SHA-256 verifier of a 256-bit cryptographically random secret, tenant, closed profile, exact subject/booking membership, family generation, issuance/expiry/revocation timestamps and optional Invoice parent. Verify the digest in constant time. Do not retain a decryptable copy of a bearer secret in models, cache, session or normal queue payloads.

Subjects and families are limited to booking payments/payment groups and standalone bookings: use explicit payment/booking foreign keys, not arbitrary polymorphic types or wildcard claims. Validate tenant consistency in addition to FKs. Snapshot the authorized booking IDs at issuance. A group credential never gains newly attached members; mismatch with the original group is refused and logged. Membership changes require a trusted, explicit reissue, not inference from a payment FK.

Each booking-payment family has a `customer_access_version` on its payment; a standalone booking has its own version. Booking-level grants belonging to a payment share that payment family. Security replacement increments the version under the tenant-first lock, invalidating every Management/Checkout/Invoice/action grant and browser session in that family. Invoice children additionally require an active parent. Individual grant revocation does not widen any other grant.

Delivery records freeze the family/version, exact profile/subject, trusted issuance reason and recorded recipient when the business operation requests delivery. Grants also retain their immutable recipient snapshot or a reference to that immutable delivery record; direct Checkout issuance captures its recipient in the booking transaction even though it returns a browser link rather than sending email. Protect recipient snapshots at rest. Renewal uses this snapshot, never a resend/provider input or a later reread of mutable contact fields. An authorized recipient-change flow must replace the family; this ADR does not add customer email editing.

### 4. Issuance and delivery

Booking creation issues Checkout access after all group members exist, inside the booking transaction, before gateway initiation after commit. Free/walk-in bookings and trusted settlement/notification flows request Management delivery. A Checkout response, invoice GET or provider return cannot issue Management authority or reveal a Management link.

For email, persist the trusted delivery intent in the business transaction; deliver only after commit. Whether delivery is synchronous now or queued under BQ-013 later, the handler validates the intent's original family version under the tenant lock and issues a fresh grant immediately before transport. Customer-authorized intents also record their source grant/Invoice parent and revalidate its revocation, generation and exact subject/rights at every issuance/retry; source expiry is tolerated only for the defined Management/Checkout renewal. Initial business-event delivery instead requires its own trusted issuance authority. Raw URLs exist transiently in memory and in the intended email. A queue job carries delivery IDs, not raw links or request objects. Page rendering and model getters never mint or reconstruct credentials.

Bound delivery attempts to three. An uncertain send may have delivered its link: a retry may issue another grant, but ordinary delivery/resend does not revoke previously delivered links. Stale intents/retries cannot issue after a security replacement because their captured generation no longer matches. Sending an already revoked link in an external transport race cannot restore its authority. No external mail call occurs while holding the tenant lock. Credential emails must not use a logging mail transport with real active credentials.

### 5. Finite lifetime and renewal

These are local product-policy choices, not durations prescribed by a security standard. Freeze `expires_at` at issuance; visits and sessions never extend it. Interpret schedule instants in the business timezone and store/compare absolute expiry instants consistently.

| Profile | Fixed expiry |
|---|---|
| Management | Earlier of `max(issued_at, latest authorized scheduled end) + 30 days` and `issued_at + 180 days` |
| Checkout | `max(issued_at, payment deadline captured at issuance) + 24 hours`; a pending checkout without a known deadline is refused |
| Invoice | `issued_at + 24 hours`, bounded by parent expiry if derived |
| Cancellation / Reschedule | Earlier of `issued_at + 24 hours` and the source schedule start; Action cutoff rules may refuse earlier |
| Review | `issued_at + 7 days`; completed-booking/review eligibility still applies |

The Checkout access window allows status/invoice access after settlement; it never extends payment eligibility or revives a terminal payment. Candidate #6 still owns payment-deadline unification. Reschedule confirmations may issue fresh Management access using current verified dates; existing grants retain their fixed expiry and are not automatically revoked or extended. Very distant reservations may require renewal before their appointment because of the 180-day cap.

A recognized expired, unrevoked Management/Checkout credential can establish a recovery-only state, valid for 15 minutes, that permits only a CSRF-protected request to send the same profile to its recorded recipient. It cannot view booking/payment data or authorize any business operation. Renewal preserves the original subject/member set and requires the current family generation; membership/recipient inconsistencies require tenant-assisted resolution, not automatic widening. Limit requests to three per hour per family and ten per hour per IP; use the same generic response for invalid/ineligible requests. No credential or recipient is returned in the response. Expired narrow links direct the customer to their Management email or tenant contact.

Unknown/revoked credentials and public identifiers alone cannot recover access. Lost/revoked-link assistance uses a tenant-authenticated resend/replacement sent only to the recorded recipient; provide a small owner operation for that purpose, not a public lookup by order ID/email. Ordinary renewal adds a fresh grant; suspected compromise uses atomic family replacement and invalidates old sessions and queued issuance.

### 6. Browser entry, origins and provider returns

All credential-bearing customer entry and private pages use the configured canonical BookQu HTTPS origin. A validated custom-domain storefront hands off there after checkout; it does not determine private origins from arbitrary Host/Referer/return parameters. Public storefront navigation still uses `CustomerBookingRoutes` (BQ-047); it does not become authorization.

New entry links carry the issue-once credential in the URL fragment of a minimal first-party bootstrap page. The fragment is not sent in HTTP URLs. First-party code removes it from browser history before any follow-up request, submits it to a same-origin POST with CSRF protection, and discards it from memory after exchange. Bootstrap has no third-party scripts/analytics. A recognized active grant establishes a server-side grant session; subsequent redirects, forms and AJAX use credential-free URLs. Bootstrap/recovery sessions rotate session identifiers without changing owner/admin identity.

Sessions contain grant references and timeout metadata, not raw credentials or cached permission decisions. Use host-only Secure/HttpOnly/SameSite=Lax cookies; customer-grant state is separately namespaced. Enforce 30-minute idle and 12-hour absolute timeouts, bounded by grant expiry. Reopening a still-valid original link can authorize a different browser/device. A single grant must cover each target/operation; do not combine partial contexts into wider authority.

Provider return URLs contain a fixed canonical destination and public payment reference only. Never send Management/Checkout credentials to providers. Existing checkout grant sessions resume the authorized view; a return without one shows a generic page without customer/payment details and directs the customer to their original link/email. A browser callback may request authorized server-side verification; it cannot assert success. Verified webhooks keep their independent provider-authentication path.

### 7. Enforcement and information exposure

Canonical access middleware resolves the credential/session to a trusted access context; `TenantMiddleware` consumes its verified tenant instead of taking a tenant ID from input. A tenant-specific route must also match its independently resolved storefront tenant. Never overwrite a conflicting tenant context. Clear request context at completion.

Every private request checks current grant/parent/family status, expiry, profile, target and membership in the database. Mutating Actions and revocation acquire the tenant row first and revalidate the session-backed grant inside the same transaction before business changes. A preflight result is not a reusable permission promise. Business mutation endpoints retain CSRF protection; GETs do not issue grants, cancel payments or synchronize provider state.

Use a uniform generic 404 for invalid private access and sanitized business refusals for authorized operations. Rate-limit bootstrap, invalid attempts, recovery, mutations and status checks with purpose-specific IP and grant/family budgets; coordinate polling intervals with those limits. Return 429 without exposing whether an identifier exists. Storage failures fail closed.

Private/bootstrap responses use `Cache-Control: no-store`, `Referrer-Policy: no-referrer` and no indexing. Exclude credential material from application/proxy/error logs, traces, model JSON, QR/print output, analytics, Calendar and WhatsApp. An invoice has only invoice-authorized data and no broader link. Share projections contain reservation facts and public references, never private management URLs or credentials. Views receive prepared permitted links/data, not token-bearing models. Payment SDK scripts receive only provider payment data in the Checkout context.

### 8. Prelaunch cutover

Invalidate all three legacy plaintext credential fields and existing grantless customer sessions when the new resolver ships. Do not hash-import exposed credentials, widen the old payment manage token or retain a fallback resolver. Old URL patterns may remain adapters for valid new grant sessions, but possession of an old token cannot obtain a new one. Retained demo/test bookings can receive new links through trusted tooling; there is no automatic email campaign.

Migrate every issuer/reader in checkout, lifecycle Actions, models, mail, views and custom-domain flows before removing legacy columns in new migrations. Keep public references stable; making order IDs less guessable is not an authorization substitute and is unnecessary for this cutover. Restrict diagnostic issuance to local/testing (BQ-010). Update legacy-compatibility tests to the explicitly accepted cutover policy, while retaining tests for each distinct authorization failure; do not merely weaken assertions.

## Consequences and verification

- A leaked Management link deliberately grants its named customer operations for one reservation family. It must not be presented as safe to forward. Narrow invoice/action profiles and secret-free navigation limit accidental exposure; revocation, expiry and recovery have explicit behavior.
- Hash-only persistence prevents secret reconstruction. Reliable email therefore needs trusted delivery intent and fresh issuance, not a getter or permanently encrypted bearer copy. Family generations prevent delayed jobs from bypassing security replacement.
- Implement BQ-001 after BQ-028/G2 and BQ-023 → BQ-067 → BQ-012. BQ-053 and BQ-052 record distinct scope/share acceptance through this module. BQ-002 uses Checkout's exact capability and ADR-0004; provider cancellation details remain candidate #4. This decision does not implement any of those items or pass G2.
- Module-interface tests cover the complete profile/operation/subject matrix, foreign tenants/bookings/payments, group membership drift, raw-secret absence, fixed expiry/time boundaries, recovery-only states, immutable Checkout/email recipients, old-token rejection, parent attenuation and session/family revocation. Test stale queued issuance after family replacement or individual source/parent revocation and uncertain send retries explicitly.
- HTTP tests cover identifier-only denial on every storefront and management route, HTML/JSON/invoice/share projections, fragment bootstrap/clean URLs, CSRF, 404/429 behavior, provider returns without a session, and canonical/custom-domain handoff. MySQL tests prove revocation/replacement versus action/issuance ordering under the tenant-first protocol. Run focused and full suites during implementation; no runtime claims follow from this ADR.

## References

The selected checks follow [OWASP Authorization](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html): deny by default and validate each request. [OWASP Session Management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html) supports protected cookies, server-side timeouts and avoiding URL/session disclosure. [OWASP URL-token guidance](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html) supports trusted origins, random secrets, expiry and referrer protection; its password-reset single-use policy is not copied onto deliberately reusable booking links.
