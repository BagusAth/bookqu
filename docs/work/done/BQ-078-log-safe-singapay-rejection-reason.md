---
id: BQ-078
title: Log SingaPay's safe rejection reason again
type: bug-fix
status: done
priority: P1
phase: 4
domain: payment
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-005]
---

## Problem

BQ-005 (PR #13) removed every provider message from SingaPay logs. `SingaPayClient` no longer logs `message`/`code` for OAuth, account-list or payment-link failures (`app/Infrastructure/Payments/SingaPay/SingaPayClient.php`). The text survives only inside the thrown exception, and every caller (`CreateBooking`, `CreateSubscriptionPayment`, `OwnerCheckoutController`) logs just `exception_type`. As a result, a refused payment link (wrong amount, wrong account, expired token) leaves no reason anywhere. The BQ-076 diagnosis was slowed by exactly this kind of missing reason.

SingaPay's `code` and `message` are vendor error descriptions, not secrets. They must still never reach users.

## Plan

1. In `SingaPayClient`, log the provider `code` and a bounded `message` (string only, truncated to about 200 characters) on the three failure paths, next to the HTTP status and `reff_no`.
2. Never log request headers, tokens, signatures, client secret or the full response body.
3. Keep user-facing messages generic (BQ-005 unchanged).

## Invariants at risk

None. NFR: never log secrets (AGENTS.md rule 12). Only the vendor's error fields are logged.

## Tests

Extend `tests/Feature/Customer/SafeOperationErrorsTest.php` or `SingaPayIntegrationTest`:
- `Http::fake` a 4xx with `code`/`message` → the log contains them, contains no token or secret, and the user response stays generic.

## Docs to update

`docs/domains/payment.md` Evidence only, if needed.

## Evidence

Done 2026-11-01 (session 5). `SingaPayClient` logs a sanitized `provider_code` (`[A-Za-z0-9_.-]`, max 40 characters) on OAuth, account and payment-link failures. The free-text message is still never logged: `SafeOperationErrorsTest` shows a provider can echo secrets into it. Test: `test_singapay_rejection_logs_only_its_sanitized_error_code`.
