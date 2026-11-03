---
id: BQ-076
title: SingaPay success never reaches local/dev; add a webhook simulator
type: bug-fix
status: done
priority: P1
phase: 4
domain: payment
requirements: [BR-PAY-001]
invariants: [INV-PAY-04]
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-032]
---

# BQ-076: SingaPay success never reaches local/dev; add a webhook simulator

Reported 2026-10-31 (and "Revisian 19/10/2026"): after a successful SingaPay payment, the owner dashboard still shows the booking `pending`, and a subscription is not activated. The team saw it in local/dev, not in production.

## Problem

- SingaPay confirms a payment only through a webhook to the URL registered in its dashboard. `SingaPayPaymentGateway::queryOutcome()` answers `unknown`, because the client has no status endpoint (BQ-074).
- A local/dev instance is not reachable from SingaPay, so it never receives the webhook and the payment stays `pending`. Before BQ-032 the status check read the local database instead, with the same result.

## Diagnosis (read-only, 2026-10-31)

Production was checked through temporary steps in `test-ssh.yml`, runs 41101764762, 41220279908 and 41220500338. The steps were removed again.

- **Webhook route works.** A JSON POST from a GitHub runner reaches Laravel and is answered `401 Invalid or expired timestamp`. No firewall blocks it.
- **No stuck SingaPay booking in production.** In the last 45 days there are 3 SingaPay booking payments: 2 failed while creating the link (OAuth/IP error on 2026-10-07) and 1 succeeded with its booking `paid`.
- **Production runs `main`, which predates the unified gateway.** Subscriptions there use Midtrans. 2 Midtrans subscription payments are stuck `pending` (2026-09-21, 2026-10-18); that code is replaced when `agathan` is released.
- **Production logs only `error`** (`logging.channels.single.level=error`), so webhook warnings would never be visible.
- **Production has no `schedule:run` cron** (BQ-020).

## Plan

1. Add `php artisan singapay:simulate-webhook {order_id} {--status=}` for local/dev. It runs a signed notification through the real webhook flow and is refused in production.
2. Document how to test SingaPay outside production (`docs/operations.md` §1).
3. Ops follow-ups for the lead:
   - set `LOG_LEVEL=warning` in production `.env`;
   - install the scheduler cron (BQ-020);
   - release `agathan` to `main` so subscriptions use SingaPay.

The permanent fix (asking SingaPay for status) is BQ-074.

## Evidence

- `tests/Feature/Console/SimulateSingaPayWebhookTest.php`: a paid simulation settles the payment and the booking through the real webhook; the command refuses in production; an unknown order fails.
- GitHub Actions run https://github.com/BagusAth/bookqu/actions/runs/41221348557 (PR #10): Quality checks, SQLite, MySQL and MariaDB passed.
