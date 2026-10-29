---
id: BQ-001
title: Implement the Management link module and protect storefront access
type: bug-fix
status: approved
priority: P0
phase: 4
domain: customer-access
requirements: [FR-BOOKING-015, FR-BOOKING-016, FR-MANAGE-001, FR-MANAGE-005, FR-MANAGE-006, FR-MANAGE-007, FR-MANAGE-008, FR-MANAGE-009, FR-MANAGE-010, FR-MANAGE-011, NFR-SEC-008, NFR-SEC-010]
invariants: [INV-TOK-01, INV-TEN-01]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-028, BQ-023, BQ-067, BQ-012]
---

# BQ-001: Implement the Management link module and protect storefront access

Design and plan accepted in [ADR-0006](../adr/ADR-0006-management-link-module.md), 2026-10-30. Agathan confirmed the module/Checkout choices and delegated the remaining candidate #3 decisions. This is Phase 4 work subject to roadmap gates; the vulnerabilities remain open until implementation and verification.

## Problem

`order_id` is predictable: `BKG-{tenantId}-{unixTime}-{100..999}` (`app/Domain/Payment/PaymentRules.php:46`). The storefront routes `customer.booking[.slug].payment|invoice|check-status|callback` accept the `order_id` alone (`app/Http/Controllers/Customer/BookingController.php:742`, `:950`). The invoice view renders the management URL including `manage_token` (`resources/views/customer/booking/invoice.blade.php:22`), and an eligible single-slot payment-group page embeds its booking's `cancellation_token` (`resources/views/customer/manage/payment-show.blade.php:434`). Anyone who guesses or sees an `order_id` can read customer data and reach eligible booking mutations through those exposed links. There is no rate limiting.

## Plan

1. Characterize existing authorization, HTML/email/share projections, provider returns and legacy-link tests. Record the intentional incompatibilities approved in ADR-0006 before replacing legacy assertions.
2. Add the module-owned grant/delivery schema and subject access-version fields using new migrations. Implement opaque hash-only credentials, exact membership snapshots, fixed profiles, finite expiry, revocation and generation-checked delivery intent. Do not import legacy secrets into new grants.
3. Build the issue/resolve/links/revoke interface with current subject/tenant checks and typed access contexts. Integrate trusted customer-access middleware with TenantMiddleware; use the tenant-first protocol and revalidate grant-backed authorization inside mutating Actions.
4. Implement canonical first-party fragment bootstrap, clean grant-session URLs, provider returns without credentials, and validated custom-domain checkout handoff. Preserve private route shapes only as new-session adapters. Keep browser commands protected by CSRF and public failures generic/rate-limited.
5. Issue Checkout access with an immutable recorded-recipient snapshot in booking creation and Management delivery from trusted business flows. Replace raw-token model getters and lazy invoice issuance. Email jobs carry delivery IDs; delivery freezes recipient/profile/generation and any authorizing grant/parent, revalidates them at each issuance/retry, and mints only immediately before transport, with bounded retries and no credential logging.
6. Implement expiry/recovery and the small tenant-authenticated resend/security-replacement operation specified in ADR-0006. Verify replacement invalidates active sessions and stale queued issuance; ordinary renewal must not revoke previously delivered links. Recovery never returns secrets, exposes data or accepts a request-supplied recipient.
7. Route checkout, management, invoice and operation-specific callers through permitted links/data. Apply private response headers and secret redaction. Implement BQ-053 scope acceptance and BQ-052 public-share acceptance through the same module; BQ-002 delegates payer cancellation to Payment lifecycle.
8. Cut over all issuers/readers together: invalidate old credentials/grantless customer sessions, remove permissive fallback and drop legacy fields only after usage is eliminated. Keep public order/booking references stable. Diagnostic issuance follows BQ-010 and the same module.
9. Test the full profile/operation/subject matrix and each distinct HTTP entry point, including foreign identifiers, rendered credential escalation, expiry/recovery, membership drift, parent attenuation, custom domains, CSRF, 404/429, clean provider returns and share redaction. MySQL proves action/revocation/replacement/issuance ordering; delivery tests cover stale generations, immutable recipients and ambiguous retries. Run focused and full suites; update domain status only with matching evidence.

## Scope and decisions

ADR-0006 is the authority for profile permissions, lifetime, recovery, delivery, storage and legacy transition. This includes the minimal schema and renewal/resend behavior accepted under the lead's explicit delegation. It adds no customer account, generic RBAC, arbitrary grant generator or provider payment policy. Candidate #4 still decides provider outcome intake; candidate #6 still decides payment deadline unification.

## Evidence

_Fill in when done._
