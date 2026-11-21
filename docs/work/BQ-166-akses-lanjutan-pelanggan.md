---
id: BQ-166
title: Prepare authorized navigation and storefront error branding
type: improvement
status: approved
priority: P1
phase: 4
domain: public-site
requirements: [FR-PUBLIC-003, FR-PUBLIC-008, FR-BOOKING-016, FR-MANAGE-002, FR-PAYMENT-008]
invariants: [INV-TEN-01, INV-TOK-01, INV-PAY-02, INV-SUB-01]
lane: heavy
approval: tech-lead
launch-blocker: false
depends-on: [BQ-165]
---

## Problem

The payment success and management views lack prepared destinations for eligible customers. The shared customer error view can receive a recovery URL, but it lacks the storefront branding used by the booking shell.

Approved by Tech Lead 2026-11-21

## Plan

1. On the successful payment invoice, resolve an existing browser session grant with Management authority for the exact payment and its booking members under ADR-0006. Give the view a credential-free canonical management destination as `manageUrl`. Give Checkout-only sessions and ineligible bookings a null value. Existing invoice access stays in force.
2. On both management views, give the customer a canonical payment-page `continuePaymentUrl` when the current Management grant covers the payment operation and the displayed booking is eligible. Use the existing payment state and expiry rules and refuse cancelled bookings.
3. Give customer error views the same prepared branding as the booking shell when the storefront middleware has established a public, active and eligible tenant for that request. Preserve existing recovery behavior and generic responses where public storefront context is unavailable or private access fails.
4. Keep the work at the view-data boundary. Existing templates and scripts remain the responsibility of the display developer.

## Invariants at risk

- INV-TOK-01 and INV-TEN-01 require exact tenant, subject, member and operation checks against live session grants. These read paths issue no grant and carry no credentials in their destinations.
- INV-PAY-02 keeps pending and expiry decisions with the payment domain. Navigation changes no payment or booking state.
- INV-SUB-01 preserves the current visibility of inactive or expired storefronts and the custom-domain entitlement gate.

## Tests

- Exercise success and both management routes with valid grants, follow the authorized destinations and verify same-subject access.
- Verify identifier-only access, a Checkout-only invoice, foreign tenant and foreign booking grants, cancellations, and an expired pending payment.
- Exercise 403, 404, 419, 500 and 503 error view data with a resolved storefront, a connected custom domain, an unknown storefront and a private access failure. Assert tenant A never receives tenant B's name or colors and inactive or expired businesses receive generic branding.
- Run Pint, related tests, the full suite once and PHPStan on PHP 8.4.25.

## Docs to update

Update `docs/domains/public-site.md` with the view-data contract and tenant visibility rules.

## Evidence

Pending implementation and verification.
