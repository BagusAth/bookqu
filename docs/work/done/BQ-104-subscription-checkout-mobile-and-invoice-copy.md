---
id: BQ-104
title: Subscription checkout mobile layout order, invoice terminology, and Snap token interpolation
type: improvement
status: rejected
priority: P2
phase: 8
domain: subscription
requirements: [FR-SUB-001, FR-SUB-004, FR-SUB-007, NFR-UX-001, NFR-UX-005, NFR-SEC-004]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: [BQ-092]
---

# BQ-104: Subscription checkout mobile layout order, invoice terminology, and Snap token interpolation

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-145](BQ-145-paket-tagihan-notifikasi-bantuan.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-145.


Found during the owner UI deep audit on 2026-11-02 across `/owner/checkout`, `/owner/checkout/payment`, and `/owner/checkout/invoice/{id}`.

## Problem

### 1. Inverted Mobile Visual Hierarchy (375px / 768px)
In `resources/views/owner/checkout.blade.php:202-214`:
- On smaller viewports below `lg:`, the responsive grid layout places the payment method selection form and "Lanjutkan Pembayaran" submit CTA above the order summary sidebar.
- The owner is presented with the final payment button before they have reviewed the subscription plan breakdown, billing cycle duration, subtotal, and taxes.

### 2. Confusing Invoice Terminology ("Kode Booking" on Subscription Invoice)
In `resources/views/owner/checkout-invoice.blade.php:62, 196`:
- The subscription receipt and invoice header labels the subscription order ID as `"Kode Booking"`:
  `<span class="text-xs text-neutral-400">Kode Booking</span>`
  `<p class="font-mono text-xs font-semibold text-neutral-900">{{ $subscriptionPayment->order_id }}</p>`
- Subscription billing is distinct from customer reservations. Calling a subscription payment a "booking" is misleading to owners. The label should be "Nomor Pesanan" or "ID Transaksi".
- The invoice print layout (`window.print()`) lacks clean print utility overrides, causing unnecessary dark borders and navigation headers to render on physical prints.

### 3. Raw String Interpolation of Snap Token
In `resources/views/owner/checkout-payment.blade.php:304`:
```blade
window.snap.pay('{{ $snapToken }}', { ... })
```
Injects the transaction token directly into raw JavaScript single quotes rather than utilizing `@js($snapToken)`.

## Plan

1. Reorder mobile checkout columns using Tailwind order utilities (`order-2 lg:order-1` for form, `order-1 lg:order-2` for order summary) so mobile users see the itemized price breakdown before the action CTA.
2. Fix terminology in `checkout-invoice.blade.php` to "Nomor Pesanan" / "ID Transaksi" and add clean `@media print` styles.
3. Wrap Snap token passing in `@js($snapToken)`.

## Invariants at risk

None. Subscription payment lifecycle and states (`INV-PAY-01`, `INV-PAY-02`) remain intact.

## Tests

- Visual / responsive test: at 375px viewport on `/owner/checkout`, order summary card precedes the submit button in DOM / visual order.
- Feature test: invoice page renders "Nomor Pesanan" and does not contain the phrase "Kode Booking".
- Browser test: Snap payment initializer loads token cleanly via `@js`.

## Docs to update

`docs/domains/subscription.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
