# RF-02 — Payment Refactor

> **Status:** Planned
> **Priority:** High
> **Phase:** Payment Boundary Consolidation
> **Integration Branch:** `Refactor`
> **Baseline Commit:** `ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8`
> **Reference Implementation:** `mergeV2`
> **Depends On:** RF-00 Foundation, RF-01 Booking where booking-payment interactions are affected
> **Purpose:** Establish a clear BookQu payment boundary while preserving all existing payment behavior.

---

# 1. Objective

Refactor the current payment implementation so that:

```text
BookQu payment business operation
```

is separated from:

```text
Midtrans provider implementation
```

The desired direction is:

```text
HTTP
 ↓
Payment Application Operation
 ↓
BookQu Payment Rules
 ↓
Payment Gateway Boundary
 ↓
Midtrans
```

The refactor must preserve:

```text
payment lifecycle
booking synchronization
subscription synchronization
webhook idempotency
payment verification
payment expiration
payment failure behavior
tenant isolation
existing invoices
```

This is an architecture refactor.

It is not a payment-system redesign.

---

# 2. Branch and Documentation Baseline

This work order is written against:

```text
Branch:
Refactor

Commit:
ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8
```

The documentation currently exists under the transition filenames described in RF-01.

Canonical target:

```text
AGENTS.md

docs/README.md
docs/PRODUCT.md
docs/REQUIREMENTS.md
docs/ARCHITECTURE.md
docs/DEVELOPMENT.md
docs/TRACKER.md
docs/REFACTOR-PLAN.md
```

If RF-00 has not yet normalized the filenames, use the existing files without creating duplicates.

---

# 3. Mandatory Reading

Before modifying payment code, read:

```text
AGENTS.md
docs/PRODUCT.md
docs/REQUIREMENTS.md
docs/ARCHITECTURE.md
docs/DEVELOPMENT.md
docs/TRACKER.md
docs/REFACTOR-PLAN.md
```

Relevant sections should be inspected specifically for:

```text
Payment
Booking and Payment separation
Subscription
Tenant isolation
Transactions
External integrations
Webhook security
Idempotency
Testing
```

---

# 4. Relevant Requirements

Primary payment requirements:

```text
FR-PAYMENT-001
FR-PAYMENT-002
FR-PAYMENT-003
FR-PAYMENT-004
FR-PAYMENT-005
FR-PAYMENT-006
FR-PAYMENT-007
FR-PAYMENT-008
FR-PAYMENT-009
FR-PAYMENT-010
```

Booking requirements affected by payment:

```text
FR-BOOKING-007
FR-BOOKING-009
FR-BOOKING-018
FR-MULTIBOOK-003
FR-MULTIBOOK-004
FR-MULTIBOOK-005
FR-MULTIBOOK-006
```

Subscription requirements affected by payment:

```text
FR-SUB-007
FR-SUB-008
FR-SUB-009
```

The actual requirement text in `REQUIREMENTS.md` remains authoritative.

Do not redefine these requirements inside this document.

---

# 5. Current Implementation

Payment logic is currently distributed across several areas.

Primary payment service:

```text
app/Services/MidtransPaymentService.php
```

Current size:

```text
≈ 491 lines
```

It currently contains:

```text
Midtrans configuration
payment verification
payment status synchronization
payment success processing
payment failure processing
payment expiration
payment pending handling
booking synchronization
subscription synchronization
transactions
payment state logic
notifications
email side effects
cache invalidation
```

Customer booking payment is also handled through:

```text
app/Http/Controllers/Customer/BookingController.php
```

Owner subscription payment is handled through:

```text
app/Http/Controllers/Owner/OwnerCheckoutController.php
```

Owner payment settings are handled through:

```text
app/Http/Controllers/Owner/OwnerSettingController.php
```

Webhook entry point:

```text
app/Http/Controllers/Webhook/MidtransWebhookController.php
```

Payment model:

```text
app/Models/Payment.php
```

---

# 6. Current Payment Hotspots

Important current methods include:

```text
MidtransPaymentService::verifyAndSync()
MidtransPaymentService::syncStatus()
MidtransPaymentService::processSuccess()
MidtransPaymentService::processFailed()
MidtransPaymentService::expirePayment()
MidtransPaymentService::processPending()
```

The largest current workflow is approximately:

```text
processSuccess()
≈ 175 lines
```

The critical problem is not only the service size.

The service currently combines:

```text
external-provider communication
+
BookQu payment state
+
booking state synchronization
+
subscription state synchronization
+
emails
+
notifications
+
cache invalidation
+
transactions
```

That creates a high coupling point.

---

# 7. Current Architectural Problem

The current implementation conceptually resembles:

```text
MidtransPaymentService
        │
        ├── Midtrans API
        ├── Payment
        ├── Booking
        ├── Subscription
        ├── Notifications
        ├── Mail
        └── Cache
```

The target is not to eliminate all coordination.

The target is to make responsibility explicit.

Preferred conceptual structure:

```text
Payment Application Operation
        │
        ├── Payment Domain Rules
        │
        ├── Booking synchronization
        │
        └── Payment Gateway
                 │
                 └── Midtrans
```

---

# 8. Core Boundary

The most important rule of RF-02 is:

> Midtrans must be an integration detail, not the definition of BookQu's payment domain.

BookQu must decide:

```text
what a payment is
what payment states mean
when a payment is successful
what booking state follows
what subscription state follows
what operations are allowed
```

Midtrans only provides:

```text
external payment-provider behavior
```

---

# 9. Target Architecture

The target direction may become:

```text
app/
├── Actions/
│   └── Payment/
│
├── Domain/
│   └── Payment/
│
├── Services/
│   └── Payment/
│
└── Infrastructure/
    └── Payments/
        └── Midtrans/
```

A possible end state:

```text
app/Actions/Payment/
├── CreateBookingPayment.php
├── CheckPaymentStatus.php
├── ProcessPaymentWebhook.php
└── CreateSubscriptionPayment.php

app/Domain/Payment/
└── PaymentState.php

app/Services/Payment/
└── PaymentGateway.php

app/Infrastructure/Payments/
└── Midtrans/
    └── MidtransPaymentGateway.php
```

These exact filenames are not mandatory.

Do not create interfaces or abstractions without a useful boundary.

---

# 10. Important Design Decision

Do not immediately rewrite `MidtransPaymentService` into ten new classes.

Use incremental extraction.

The desired direction is:

```text
Existing MidtransPaymentService
        ↓
Separate external provider operations
        ↓
Separate BookQu application operations
        ↓
Separate payment state rules
        ↓
Reduce service responsibility
```

The architecture should emerge through meaningful responsibility boundaries.

---

# 11. Refactor Sequence

RF-02 must follow this order:

```text
Step 1
Payment behavior baseline

↓
Step 2
Characterization / regression coverage

↓
Step 3
Identify payment lifecycle

↓
Step 4
Isolate Midtrans provider operations

↓
Step 5
Create booking payment operation boundary

↓
Step 6
Create webhook processing boundary

↓
Step 7
Centralize payment state transitions

↓
Step 8
Separate subscription payment

↓
Step 9
Reduce MidtransPaymentService

↓
Step 10
Payment architecture verification
```

Do not start by changing the database.

---

# 12. Step 1 — Payment Baseline

Run and record existing tests before changing code.

At minimum inspect:

```text
tests/Feature/Customer/PaymentGroupManagementTest.php
tests/Feature/Customer/ProductionLogicSpecificationTest.php
tests/Feature/Owner/CheckoutMidtransTest.php
tests/Feature/SubscriptionRulesAndMechanismsTest.php
tests/Feature/CoreFlowIntegrationTest.php
tests/Feature/P0SecurityTest.php
```

Also inspect related booking tests because payment affects booking state.

Record:

```text
full suite result
payment-specific result
webhook result
subscription-payment result
booking-payment result
```

---

# 13. Step 2 — Payment Characterization

Protect behavior for:

```text
successful booking payment
failed booking payment
pending booking payment
expired booking payment
duplicate callback
late callback
malicious callback
multi-slot payment
payment group
subscription payment
```

Also protect:

```text
booking synchronization
invoice availability
booking status transition
cache invalidation
notification side effects
```

Use existing tests when coverage is already sufficient.

Do not create redundant tests only to satisfy a checklist.

---

# 14. Step 3 — Establish Payment Lifecycle

Before extracting code, explicitly map the current lifecycle.

The agent must inspect:

```text
Payment::status
Booking::status
Subscription::status
Midtrans transaction status
```

and establish the existing mapping.

For example, conceptually:

```text
External Status
      ↓
BookQu Payment Status
      ↓
BookQu Business Side Effect
```

The exact mapping must come from the current implementation and `REQUIREMENTS.md`.

Do not invent a new state machine.

---

# 15. Payment and Booking State Must Remain Separate

Do not turn:

```text
payment status
```

into:

```text
booking status
```

They are separate domains.

The application may synchronize them according to requirements, but they remain conceptually distinct.

For example:

```text
Payment
    sukses

does not mean:

Payment = Booking
```

Instead:

```text
Payment success
        ↓
BookQu payment state
        ↓
required booking synchronization
```

---

# 16. Step 4 — Isolate Midtrans

The first technical extraction should be external-provider specific functionality.

Identify operations such as:

```text
configure provider
create payment
fetch transaction status
verify transaction
```

Move provider-specific behavior toward:

```text
app/Infrastructure/Payments/
```

or another infrastructure location consistent with `ARCHITECTURE.md`.

The application layer must no longer depend on Midtrans SDK calls wherever the provider boundary has already been established.

---

# 17. Gateway Boundary

The boundary should conceptually expose BookQu-friendly operations.

For example:

```text
PaymentGateway
    create
    getStatus
    verify
```

The exact method names may differ.

The important distinction is:

```text
Application
    ↓
Gateway capability
```

instead of:

```text
Application
    ↓
Midtrans SDK
```

Do not expose Midtrans-specific types throughout the application layer.

---

# 18. Do Not Over-Abstraction

Do not introduce a gateway interface merely because architecture diagrams normally contain interfaces.

Create the boundary because:

```text
Midtrans implementation should be replaceable
or
application logic should be testable independently
or
provider-specific code is currently leaking across layers
```

If an existing service can be cleanly converted into the boundary without excessive abstraction, that is acceptable.

---

# 19. Step 5 — Booking Payment Operation

Create an explicit application operation for booking payment.

Possible direction:

```text
app/Actions/Payment/CreateBookingPayment.php
```

Responsibilities may include:

```text
create BookQu payment record
prepare provider payload
invoke payment gateway
persist external reference
persist provider response data
return application-level result
```

Do not make this Action responsible for all later webhook state synchronization.

Those are separate concerns.

---

# 20. Free Booking

The current booking system supports a free-payment path.

Preserve it exactly.

Do not force a free booking through a payment provider when the current requirement does not require that.

Free booking must continue to produce the current expected:

```text
Payment
Booking
Status
Invoice
Management
```

behavior.

---

# 21. Multi-Slot Payment

Multi-slot bookings currently use a unified payment/payment-group concept.

RF-02 must preserve:

```text
one payment group
multiple related bookings
unified payment state
unified invoice behavior
```

Do not replace the current relationship merely to simplify the architecture.

The application operation should understand the BookQu concept.

The provider should not need to understand BookQu's internal multi-slot domain model.

---

# 22. Step 6 — Webhook Boundary

Current webhook entry point:

```text
app/Http/Controllers/Webhook/MidtransWebhookController.php
```

Target flow:

```text
Midtrans Request
       ↓
Webhook Controller
       ↓
Verify / Parse Provider Payload
       ↓
Payment Application Operation
       ↓
Payment State
       ↓
Business Synchronization
```

The controller should eventually remain mostly an HTTP adapter.

---

# 23. Webhook Security

Never trust:

```text
browser callback
frontend result
client payment status
```

The payment-provider callback must be verified according to the existing security requirements.

Do not weaken:

```text
signature verification
trusted status handling
order/payment resolution
tenant association
```

during the refactor.

---

# 24. Webhook Idempotency

Repeated callbacks are expected behavior in external payment systems.

The refactor must preserve idempotency.

A repeated successful callback must not accidentally:

```text
create duplicate bookings
create duplicate payment records
repeat state transitions incorrectly
create duplicate refunds
send unintended duplicate side effects
```

The current locking/idempotency strategy must be understood before modifying `processSuccess()`.

---

# 25. Payment Concurrency

The current payment implementation uses transactions and row-level locking.

For example, successful payment processing currently locks the payment record before synchronization.

Do not remove:

```text
DB transaction
lockForUpdate()
idempotency checks
```

until an equivalent or stronger mechanism has been established.

---

# 26. Step 7 — Payment State Boundary

Payment state logic should eventually have one identifiable owner.

Possible:

```text
app/Domain/Payment/PaymentState.php
```

or another focused state mechanism.

The implementation must define valid transitions without scattering them across:

```text
controllers
webhook
provider service
models
console commands
```

Do not change state semantics during RF-02.

This is a structural extraction.

---

# 27. Provider Status vs BookQu Status

Maintain a distinction between:

```text
Midtrans transaction status
```

and:

```text
BookQu payment status
```

The provider may return states that do not map one-to-one to the application's state.

Therefore:

```text
Provider payload
        ↓
Provider interpretation
        ↓
BookQu payment decision
        ↓
BookQu state transition
```

Do not expose provider-specific state directly as application business state without interpretation.

---

# 28. Step 8 — Subscription Payment Separation

Booking payment and subscription payment may use the same provider.

They remain different business operations.

Target:

```text
Booking Payment
        ↓
Payment Gateway
```

and:

```text
Subscription Payment
        ↓
Payment Gateway
```

not:

```text
One giant payment workflow
        ↓
switch tipe pembayaran everywhere
```

The existing `Payment::tipe` semantics must be understood and preserved.

---

# 29. Subscription Payment Scope

RF-02 may extract subscription payment-specific application operations.

However:

```text
subscription entitlement
subscription lifecycle
trial rules
usage limits
feature access
```

belong primarily to:

```text
RF-06-SUBSCRIPTION.md
```

Do not refactor all subscription architecture as part of RF-02.

---

# 30. Step 9 — Reduce MidtransPaymentService

Only after the previous boundaries are established should the existing:

```text
MidtransPaymentService
```

be reduced.

The desired result is that it no longer acts as:

```text
payment domain
+
booking service
+
subscription service
+
notification service
+
email service
+
cache service
+
Midtrans client
```

Its remaining responsibilities should be clearly bounded.

It may:

```text
coordinate provider-specific payment operations
```

or become the concrete provider adapter, depending on the resulting architecture.

Do not rename or delete it simply because a new class exists.

Verify all references first.

---

# 31. Payment Model Responsibilities

`app/Models/Payment.php` may retain:

```text
relationships
casts
route binding behavior
small entity-level methods
simple state predicates
```

Do not move all payment lifecycle operations into the model.

Do not turn the Payment model into another God Model.

---

# 32. Route Model Binding

The current `Payment` model contains custom route-binding behavior that bypasses tenant scope.

This is security-sensitive.

The current implementation exists because route binding occurs before tenant middleware context is established.

Do not remove or alter the bypass without verifying the entire route-binding sequence and its authorization implications.

Any change here requires:

```text
security test
route test
tenant isolation verification
```

---

# 33. Tenant Isolation

Payment operations must always preserve:

```text
payment tenant
booking tenant
subscription tenant
owner tenant
```

Never accept a payment identifier as sufficient authorization.

Verify:

```text
payment ownership
payment purpose
tenant association
booking association
subscription association
```

before protected operations.

---

# 34. Global Scope Bypass

Existing payment code uses global-scope bypasses in places such as:

```text
Payment
Booking
```

These are security-sensitive.

For each bypass:

```text
Identify why it exists.
Identify what authorizes access.
Verify tenant association.
Verify route/request source.
Add regression protection if needed.
```

Do not globally remove every:

```text
withoutGlobalScope()
withoutGlobalScopes()
```

during this refactor.

---

# 35. Transactions

Payment operations that modify multiple authoritative records should use appropriate transaction boundaries.

Examples:

```text
payment success
payment failure
payment expiration
booking synchronization
subscription synchronization
```

Do not wrap provider network calls inside database transactions unless the current architecture specifically requires it.

Prefer:

```text
Provider interaction
        ↓
Application decision
        ↓
Database transaction
        ↓
State synchronization
```

where appropriate.

---

# 36. Side Effects

Current payment processing may trigger:

```text
email
notification
cache invalidation
```

Do not remove these side effects during extraction.

First identify:

```text
which side effect belongs to which state transition
```

Then preserve it.

Do not introduce queues/events solely for architectural appearance.

---

# 37. Cache Invalidation

Payment success can affect booking availability.

Preserve existing cache invalidation behavior.

The detailed availability cache architecture belongs to:

```text
RF-03-SCHEDULE.md
```

For RF-02:

```text
Preserve correct invalidation.
```

Do not redesign the entire cache system.

---

# 38. Files / Areas In Scope

Primary areas:

```text
app/Services/MidtransPaymentService.php
app/Http/Controllers/Customer/BookingController.php
app/Http/Controllers/Owner/OwnerCheckoutController.php
app/Http/Controllers/Owner/OwnerSettingController.php
app/Http/Controllers/Webhook/MidtransWebhookController.php
app/Models/Payment.php
```

Potential target locations:

```text
app/Actions/Payment/*
app/Domain/Payment/*
app/Services/Payment/*
app/Infrastructure/Payments/*
```

Relevant tests may be modified or added.

Additional files may only be changed when directly required.

---

# 39. Restricted Areas

Do not use RF-02 to perform:

```text
booking domain redesign
schedule redesign
subscription entitlement redesign
database schema rename
full owner controller decomposition
Blade redesign
route redesign
provider replacement
```

Those belong to other work orders.

---

# 40. Forbidden Changes

Do not:

```text
change payment status meanings
change booking state meanings
change subscription state meanings
change Midtrans account/configuration semantics
remove webhook verification
remove idempotency protection
remove database locks without replacement
rename database columns
remove payment-group behavior
change invoice rules
change customer-visible payment flow
replace Midtrans
rewrite all payment tests
```

Do not add a second payment state system alongside the existing one.

---

# 41. Testing Requirements

At minimum verify:

```text
successful booking payment
failed booking payment
pending payment
expired payment
payment status check
webhook processing
webhook idempotency
malicious/invalid callback
late callback
multi-slot payment
payment group
invoice after successful payment
booking synchronization
subscription payment
```

Also verify:

```text
tenant isolation
authorization
route binding
```

---

# 42. Failure Handling

When a test fails after extraction:

First determine whether the failure is:

```text
behavior regression
existing implementation defect
test mismatch
requirement conflict
environment problem
```

Do not weaken assertions simply to complete the refactor.

---

# 43. Acceptance Criteria

RF-02 is complete only when:

```text
[ ] BookQu payment logic has an explicit application boundary
[ ] Midtrans-specific behavior is isolated
[ ] Booking payment is separated conceptually from subscription payment
[ ] Payment state transitions have a clear owner
[ ] Webhook handling is an HTTP adapter over application logic
[ ] Webhook idempotency remains intact
[ ] Payment verification remains intact
[ ] Booking/payment synchronization remains intact
[ ] Multi-slot payment groups remain intact
[ ] Invoice behavior remains intact
[ ] Tenant isolation remains intact
[ ] Route-binding security remains intact
[ ] Existing payment tests pass
[ ] Relevant booking tests pass
[ ] Subscription-payment tests pass
[ ] Midtrans-specific coupling is materially reduced
[ ] No unrelated product behavior was introduced
[ ] TRACKER.md is updated
[ ] ARCHITECTURE.md is updated where required
```

---

# 44. Quality Criteria

The refactor is not considered successful merely because:

```text
MidtransPaymentService is smaller
```

or:

```text
A PaymentGateway interface exists
```

Success means:

```text
BookQu payment rules
        ↓
are understandable independently
of the provider implementation.
```

A developer should be able to understand:

```text
What BookQu considers a payment
```

without reading the Midtrans SDK implementation.

---

# 45. Stop Conditions

Stop and report a blocker when:

```text
payment state semantics are ambiguous
provider status mapping is unclear
booking-payment synchronization behavior is contradictory
subscription-payment behavior is unclear
webhook trust rules are unclear
tenant authorization cannot be established
existing tests do not protect critical behavior
database semantics must change
```

Do not invent a state transition or payment rule.

---

# 46. Agent Decision Rule

When choosing between implementations:

Prefer:

```text
clear payment boundary
+
preserved state semantics
+
smallest coherent extraction
+
existing Laravel patterns
+
testable application operation
```

Do not over-engineer.

Do not introduce every possible DDD abstraction.

---

# 47. Completion Report

When RF-02 is complete, report:

```text
RF:
RF-02-PAYMENT

Branch:
Refactor

Baseline Commit:
ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8

Current Commit:
<commit>

Payment Application Operations:
<list>

Payment Domain Components:
<list>

Provider / Infrastructure Components:
<list>

Midtrans Coupling Removed:
<summary>

Controllers Changed:
<list>

Services Changed:
<list>

Models Changed:
<list>

Tests Added:
<list>

Tests Executed:
<list>

Test Result:
<result>

Requirements Covered:
<IDs>

Behavior Changes:
None, unless explicitly documented.

Remaining Payment Debt:
<list>

Tracker Updated:
Yes/No

Architecture Documentation Updated:
Yes/No

Blockers:
<list>
```

---

# 48. Final Rule

RF-02 must move the implementation toward:

```text
BookQu Payment
      ↓
Application Operation
      ↓
Payment Rules
      ↓
Provider Boundary
      ↓
Midtrans
```

without changing what BookQu means by:

```text
payment
payment state
booking synchronization
subscription payment
webhook
invoice
payment group
```

The work must remain aligned with:

```text
PRODUCT.md
REQUIREMENTS.md
ARCHITECTURE.md
DEVELOPMENT.md
TRACKER.md
REFACTOR-PLAN.md
```

and must not silently redefine product behavior.
