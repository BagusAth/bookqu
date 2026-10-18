# ADR-0001: Unified Payment Gateway Architecture (SingaPay & Midtrans)

> **Status:** Accepted  
> **Date:** 2026-10-18  
> **Deciders:** Engineering Team / Antigravity Agent  
> **Consulted:** Technical Audit 2026-10-17, Documentation Map (`docs/1-README.md`), Architecture (`docs/4-ARCHITECTURE.md`)  
> **Informed:** Core Developers, Operations Team  

---

## 1. Context and Problem Statement

BookQu previously relied exclusively on **Midtrans** for all online payment processing, covering two core business operations:
1. **Customer Booking Payment:** Generating Midtrans Snap tokens for modal popups in customer-facing booking flows.
2. **Owner Subscription Checkout:** Generating Midtrans Snap tokens for tenant plan upgrades and subscription purchases.

A business requirement arose to adopt **SingaPay** as the primary active payment provider (in sandbox development on branch `agathan` and later staging/production), while strictly retaining Midtrans as an immediate, fully functional backup. The system must allow switching between the two providers seamlessly via configuration without code modifications ("tidak setengah-setengah"), while preserving historical transaction isolation so existing payment records can continue resolving webhooks and status checks through their native provider.

### Technical Differences Between Providers

| Dimension | Midtrans | SingaPay |
|---|---|---|
| **Integration Mechanism** | Snap Token via API (`/snap/v1/transactions`), rendered in modal via `snap.js`. | Payment Link API (`/api/v1/checkout`), redirected to hosted checkout URL (`payment_url`). |
| **Authentication** | Server Key via HTTP Basic Auth / Header. | API Key, Client ID, Client Secret, and Access Token. |
| **Webhook Security** | SHA-512 signature hash over `order_id + status_code + gross_amount + ServerKey`. | HMAC-SHA256 signature over payload + request timestamp replay protection (`X-TIMESTAMP`). |
| **Transaction Expiry** | Managed via Snap parameters (`expiry.duration`). | Managed via `expired_at` (epoch milliseconds or ISO timestamp). |
| **Status Synchronization** | `MidtransTransaction::status($orderId)` API. | Server-to-server webhook confirmation + status query. |

---

## 2. Decision Drivers

1. **Uniformity Across Product Areas:** Both Customer Booking and Owner Subscription Checkout must use the active provider consistently. No mixed state or partial migration.
2. **Zero-Code Switching:** Switching between SingaPay and Midtrans must require only updating an environment variable (`PAYMENT_GATEWAY_DRIVER`) and clearing the configuration cache.
3. **Historical Isolation:** Transactions created under Midtrans must resolve status checks and webhooks via Midtrans, even if the active driver is later changed to SingaPay (and vice versa).
4. **Preservation of Core Boundaries:** Core booking and subscription logic must remain agnostic to provider-specific payloads (Snap tokens, payment links, proprietary webhook formats).
5. **Backwards Compatibility:** Existing test suites (e.g. `CheckoutMidtransTest`, `BookingFlowTest`) must remain 100% green without regressions.
6. **Deployment Safety:** No modification to auto-deployment workflows in `.github/workflows/`.

---

## 3. Considered Options

* **Option 1: Direct if-else branching in Controllers and Actions.**
  * *Pros:* Quick to hack.
  * *Cons:* Severe code duplication across `BookingController`, `OwnerCheckoutController`, `CreateBookingPayment`, `CreateSubscriptionPayment`, `CheckPaymentStatus`, and Blade templates. Violates Open/Closed Principle. High risk of developer oversight.

* **Option 2: Unified Payment Gateway Manager with Driver Pattern (Chosen).**
  * Define `PaymentGatewayInterface` contract.
  * Implement `PaymentGatewayManager` factory resolving active driver or payment-specific driver.
  * Implement concrete `SingaPayPaymentGateway` and `MidtransPaymentGateway`.
  * Centralize configuration in `config/payment.php`.
  * Adapt views conditionally based on the payment's provider.

---

## 4. Decision Outcome

We decided on **Option 2: Unified Payment Gateway Manager with Driver Pattern**.

### Architecture Overview

```text
               config/payment.php (PAYMENT_GATEWAY_DRIVER=singapay|midtrans)
                                      │
                                      ▼
                        PaymentGatewayManager
                        ┌─────────────┴─────────────┐
                        ▼                           ▼
            SingaPayPaymentGateway        MidtransPaymentGateway
            (PaymentGatewayInterface)    (PaymentGatewayInterface)
                        │                           │
                        ▼                           ▼
               SingaPay Client/API          Midtrans Snap/API
```

### Component Details

1. **Contract (`app/Contracts/Payments/PaymentGatewayInterface.php`):**
   - `getDriverName(): string`
   - `createBookingPayment(Payment $payment, Service $service, array $customerData, int $slotCount = 1): array`
   - `createSubscriptionPayment(Payment $payment, Plan $plan, Tenant $tenant, array $payerData): array`
   - `checkTransactionStatus(Payment $payment): array`

2. **Manager (`app/Infrastructure/Payments/PaymentGatewayManager.php`):**
   - `driver(?string $name = null)`: Resolves active driver for new transactions.
   - `forPayment(Payment $payment)`: Resolves driver based on `$payment->provider ?: $payment->metode`, preserving historical isolation.

3. **Central Configuration (`config/payment.php`):**
   - `default => env('PAYMENT_GATEWAY_DRIVER', 'singapay')`
   - Dedicated config sections for `drivers.singapay` and `drivers.midtrans`.

4. **Action Refactoring:**
   - `CreateBookingPayment::initializePayment()` uses `PaymentGatewayManager`.
   - `CreateSubscriptionPayment::execute()` uses `PaymentGatewayManager`.
   - `CheckPaymentStatus::execute()` delegates to the resolved driver.

5. **Frontend Adaptations:**
   - `resources/views/owner/checkout-payment.blade.php`: Renders "Bayar Sekarang via SingaPay" link if provider is SingaPay; renders Snap modal button and loads `snap.js` only if provider is Midtrans.
   - `resources/views/customer/booking/payment.blade.php`: Loads `snap.js` only when `clientKey` and `snapUrl` are non-empty.

---

## 5. Consequences

### Positive Consequences
* **Single point of configuration:** Developers and operators switch providers with one variable in `.env`: `PAYMENT_GATEWAY_DRIVER=singapay` or `midtrans`.
* **Zero code rewrite on switch:** All flows (booking, subscription, status checks) automatically adapt.
* **Extensible:** Future gateways (e.g. Xendit, Doku) can be plugged in simply by creating a class implementing `PaymentGatewayInterface` and registering it in `PaymentGatewayManager`.
* **Historical safety:** Historical payments in production remain unaffected by global driver switches.
* **Test suite integrity:** 368 of 368 tests passing (1,737 assertions, 0 failures).

### Operational Guidelines for Developers & Teams
* **Local Development:** Default to `PAYMENT_GATEWAY_DRIVER=singapay` with sandbox credentials.
* **Running Tests:** All payment tests can be verified via `php artisan test tests/Feature/Payment/`. Tests mocking Midtrans (e.g. `CheckoutMidtransTest`) explicitly override `config(['payment.default' => 'midtrans'])` in `setUp()`.
* **Webhooks:** Webhook endpoints are distinct (`/webhook/singapay` and `/webhook/midtrans`), both validating incoming signatures and ensuring cross-provider rejection.
