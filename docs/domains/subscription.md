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
| Sidebar decides "Pro" with `str_contains(namapaket, 'pro')` (`AppServiceProvider.php:68`); expiry command updates status with raw queries | BQ-033 |
| Quota also re-checked in `BookingController::selectDate` | BQ-029 |
| Audit P1: "Change plan" button dead, sidebar subscription card wrong for paid plans | BQ-066 |
