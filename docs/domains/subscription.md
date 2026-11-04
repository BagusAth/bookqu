# Subscription & billing

Owns plans, the tenant's subscription state, trials, feature entitlement, usage limits, and subscription checkout.

## Entry points

| Kind | Where |
|---|---|
| Overview | `owner.subscription` → `Owner/OwnerSubscriptionController` → `Actions/Subscription/GetSubscriptionOverview` |
| Checkout | `owner.checkout`, `owner.checkout.process`, `owner.checkout.payment`, `owner.checkout.check-status`, `owner.checkout.callback`, `owner.checkout.invoice` → `Owner/OwnerCheckoutController` → `Actions/Payment/CreateSubscriptionPayment`; activation via `SynchronizePaymentStatus` → `ActivateSubscription` |
| Trial | Registration → `Actions/Subscription/CreateTrialSubscription` (7 days, Pro-equivalent) |
| Feature gate | middleware `subscription:<feature>` (`CheckSubscription`), used on `owner.analytics*` (`medium`) and `owner.landing-page*` (`pro`) |
| Expiry | `app:check-expired-subscriptions` daily (`Console/Commands/CheckExpiredSubscriptions`) |

## Rules (authoritative)

| Class | Owns |
|---|---|
| `App\Domain\Subscription\EntitlementRules` | `canAccessFeature`, `canCreateService`, `canCreateStaff`, `canCreateBooking`, unlimited checks |
| `App\Domain\Subscription\PlanCapability` | Plan levels `small` < `medium` < `pro`; feature names; limits |
| `App\Domain\Subscription\SubscriptionState` | States, expiry, trial days, transitions |
| `App\Domain\Subscription\SubscriptionUsage` | Counting services, staff, monthly bookings |

## Data

`plans`: `namapaket`, `hargabulanan`, `maxlayanan`, `maxbooking`, `isunlimited`. `subscriptions`: `idtenant`, `idplan`, `status`, `trial_berakhir`, `langganan_mulai`, `langganan_berakhir`. The latest subscription row is the current one.

## Invariants

- **INV-SUB-01** — feature access and limits are decided by `EntitlementRules`.

## Evidence

`tests/Unit/Domain/EntitlementRulesTest.php`, `PlanCapabilityTest.php`, `SubscriptionStateTest.php`, `tests/Feature/Subscription/SubscriptionActionTest.php`, `SubscriptionRulesAndMechanismsTest.php`, `Owner/AnalyticsAndSubscriptionTest.php`, `Owner/CheckoutMidtransTest.php`.

## Status

Functional: Implemented · Verification: Verified (partial) · Architecture: Target, with gaps below.

## Known gaps

| Gap | Item |
|---|---|
| `CheckSubscription` lets the request through when no tenant is resolved | BQ-006 |

| What an expired tenant may still do, trial-to-paid timing, renewal losing remaining paid days, reminders | BQ-139 |
| Expiry command updates status with raw queries | BQ-130 |
| Quota also re-checked in `BookingController::selectDate` | BQ-029 |
| Audit P1: "Change plan" button dead, sidebar subscription card wrong for paid plans | BQ-066 |
| Checkout mobile layout order inverted, subscription invoice labels order as "Kode Booking", and unquoted Snap token | BQ-145 |

Target decisions: [ADR-0011](../adr/ADR-0011-server-side-release-gating.md) (release gate separate from entitlement; both fail closed, BQ-006/BQ-033) and [ADR-0009](../adr/ADR-0009-pending-payment-lifetime.md) (subscription payments swept, BQ-071). Not implemented yet.

BQ-086: creation and unlimited checks fail closed without a date-active subscription and plan (`hasEffectivePlan`); Pro badge uses `isPro`. Storefront date validation and transactional creation refuse inactive tenants with neutral copy. Expired owners retain dashboard/subscription access with a renewal banner. Evidence: `ExpiredEntitlementTest` (four regressions), updated happy-path fixtures retain their original assertions.

BQ-130: `config/plans.php` is the capability/text/team-limit map; `EntitlementRules` denies unregistered capabilities, including trials. Prices and numerical booking/service limits remain in plans per ADR-0021 decision 4. `advertisedFeatures` omits unbuilt modules from both comparisons. Overview limits and landing-page entitlement use the same rules. Remaining plan-name architecture allowances were removed.

BQ-139: Renewals record their source payment once under the tenant lock. Renewing the same active paid plan extends the paid-through date. A plan change or trial conversion starts a new period at payment. Trial use is recorded per account. After expiry the storefront retains its catalog and existing payment links, while new bookings and owner creation are gated. H-3, H-1 and end-date reminders use durable per-period receipts.
