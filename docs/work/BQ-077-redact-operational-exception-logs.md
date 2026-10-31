---
id: BQ-077
title: Redact raw exception diagnostics outside booking checkout
type: bug-fix
status: proposed
priority: P2
phase: 6
domain: notifications
requirements: [NFR-SEC-010]
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-005]
---

## Problem

BQ-005's getMessage search found raw exception text in operational/service diagnostics outside its customer checkout/payment boundary: `app/Console/Commands/ExpireBookingPayments.php`, `CheckExpiredSubscriptions.php`, and `app/Actions/Service/CreateService.php`. These values do not flow into customer HTTP responses, but may copy dependency/SQL/mail details into logs or console output. No live secret exposure was established.

## Plan

Replace unbounded exception text with exception type/location and payment/tenant/service identifiers. Preserve command exit behavior and notification isolation; test representative dependency failures containing fixture secrets. Keep operator diagnosis useful without logging exception objects or sensitive payloads.

## Invariants at risk

Do not change expiry lifecycle, subscription eligibility or service creation behavior.

## Tests

Failure-path console/log assertions with fixed time and fake dependencies. No live payment or mail calls.

## Docs to update

Notifications/payment Evidence only where behavior changes.

## Evidence

Recorded 2026-11-01 during BQ-005; implementation deliberately deferred beyond the user's three-task run.
