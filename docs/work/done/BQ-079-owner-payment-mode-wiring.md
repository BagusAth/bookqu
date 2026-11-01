---
id: BQ-079
title: Decide and finish the tenant-owned Midtrans payment mode
type: bug-fix
status: done
priority: P1
phase: 4
domain: payment
requirements: []
invariants: [INV-TEN-01]
launch-blocker: true
needs-lead-approval: true
depends-on: [BQ-004, BQ-015]
---

## Problem

Owners can choose `payment_mode = owner` and save their own Midtrans keys (`owner/payment-settings`, `UpdatePaymentSettings`, encrypted by BQ-004). The booking path only partly honours this setting (found in the BQ-002/004/005 audit, 2026-11-01).

- **Default driver `singapay` (current configuration):** `CreateBookingPayment` always uses `gatewayManager->driver()`, so an owner-mode tenant's bookings still go to platform SingaPay. The tenant's keys are ignored and the money lands with the platform, although the owner believes it goes to their own account.
- **Default driver `midtrans`:** `MidtransPaymentGateway::configureForPayment` creates the Snap token with the tenant's server key and environment. `BookingController::showPayment` (`app/Http/Controllers/Customer/BookingController.php:737`) still sends the platform `client_key` and `snap_url` to `customer/booking/payment.blade.php`. When the tenant's keys or environment differ from the platform's, Snap rejects the token and the customer cannot pay.
- `RequestOwnerPayout` refuses payouts for non-platform tenants. If owner-mode money actually goes to the platform, it is never paid out (BQ-015).

## Plan

1. **Lead decides** whether v1 supports tenant-owned Midtrans:
   - **(A) Not in v1:** hide or disable the owner option, force `platform` on save, and add a migration or command that reports any tenant already set to `owner`.
   - **(B) Supported:** route owner-mode bookings to the Midtrans driver whatever the platform default, and add a tenant-aware `clientKey`/`snapUrl` resolver on the gateway (behind `PaymentGatewayInterface`, ADR-0007). Use it in `BookingController::showPayment`, and keep subscriptions on the platform.
2. Whichever option is chosen, make the owner dashboard and settings copy match the real behaviour.

## Invariants at risk

INV-TEN-01: the resolver must read only the payment's own tenant. INV-PAY-*: webhook signatures for owner-mode payments must keep using that tenant's server key (already in `resolveServerKey`).

## Tests

- **(A):** saving `owner` is refused or normalised; a booking for a legacy owner-mode tenant goes to the platform gateway.
- **(B):** an owner-mode booking creates a Midtrans payment even with default `singapay`; the payment page renders the tenant's client key and snap URL; another tenant's keys are never used.

## Docs to update

`docs/domains/payment.md` (Known gaps), `docs/domains/tenancy.md`, and `docs/requirements.md` if (B) adds a requirement.

## Evidence

Done 2026-11-01. Lead chose option A: no tenant-owned Midtrans mode in v1. The direct-payment settings are hidden by the BQ-008 module gate (404). Migration `2026_11_01_120000_force_platform_payment_mode` moves every `owner` tenant to `platform` and keeps the encrypted keys; it is irreversible on purpose. Test: `PlatformPaymentModeMigrationTest`. Releasing the mode later needs the option (B) work from this item.
