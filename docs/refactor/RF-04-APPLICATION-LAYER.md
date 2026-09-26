# RF-04 — Application Layer Refactor

> **Status:** Planned
> **Priority:** High
> **Phase:** Application Layer Consolidation
> **Integration Branch:** `Refactor`
> **Baseline Commit:** `ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8`
> **Reference Implementation:** `mergeV2`
> **Depends On:** RF-00 Foundation, RF-01 Booking, RF-03 Schedule
> **Related Work:** RF-02 Payment
> **Purpose:** Move application workflows out of controllers and establish clear HTTP → Application boundaries.

---

# 1. Objective

Refactor the current controller-centric application layer so that controllers become thin HTTP adapters while application operations become explicit.

Target direction:

```text
HTTP Request
     ↓
Form Request / Authorization
     ↓
Controller
     ↓
Application Action
     ↓
Domain Rules / Models / Services
     ↓
Response
```

The objective is not to eliminate controllers.

The objective is to make controllers responsible for HTTP concerns rather than complete business workflows.

---

# 2. Branch and Documentation Baseline

This work order is based on:

```text
Branch:
Refactor

Commit:
ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8
```

Canonical documentation target:

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

The current repository may still contain transitional filenames until RF-00 is completed.

Do not create duplicate documentation.

---

# 3. Mandatory Reading

Before changing application-layer code, read:

```text
AGENTS.md
docs/PRODUCT.md
docs/REQUIREMENTS.md
docs/ARCHITECTURE.md
docs/DEVELOPMENT.md
docs/TRACKER.md
docs/REFACTOR-PLAN.md
RF-01-BOOKING.md
RF-02-PAYMENT.md
RF-03-SCHEDULE.md
```

Where RF-01, RF-02, or RF-03 are not yet complete:

> Inspect their intended boundaries but do not assume their target classes already exist.

---

# 4. Scope

RF-04 covers:

```text
Owner controllers
Customer controllers not already structurally handled by RF-01
Owner dashboard
Owner settings
Owner portal
Owner customer management
Owner service management
Owner schedule entry points
Owner checkout entry points
Form Requests
Controller validation boundaries
Controller authorization boundaries
Application Action organization
```

The focus is the application layer.

This phase does not redesign the UI.

---

# 5. Current Controller Landscape

The current repository contains approximately 24 controllers.

Important owner controllers include:

```text
app/Http/Controllers/Owner/OwnerDashboardController.php
app/Http/Controllers/Owner/OwnerPortalController.php
app/Http/Controllers/Owner/OwnerBookingController.php
app/Http/Controllers/Owner/OwnerCustomerController.php
app/Http/Controllers/Owner/OwnerProgramController.php
app/Http/Controllers/Owner/OwnerScheduleController.php
app/Http/Controllers/Owner/OwnerSettingController.php
app/Http/Controllers/Owner/OwnerCheckoutController.php
app/Http/Controllers/Owner/OwnerStaffResourceController.php
app/Http/Controllers/Owner/OwnerAdditionalItemController.php
app/Http/Controllers/Owner/OwnerCategoryController.php
app/Http/Controllers/Owner/OwnerVoucherController.php
app/Http/Controllers/Owner/OwnerReviewController.php
app/Http/Controllers/Owner/OwnerAnalyticsController.php
app/Http/Controllers/Owner/OwnerAssetController.php
app/Http/Controllers/Owner/OwnerLandingPageController.php
app/Http/Controllers/Owner/OwnerNotificationController.php
app/Http/Controllers/Owner/OwnerSubscriptionController.php
```

Important customer controllers:

```text
app/Http/Controllers/Customer/BookingController.php
app/Http/Controllers/Customer/BookingManageController.php
```

RF-01 handles the major booking-domain extraction.

RF-04 should not undo or duplicate that work.

---

# 6. Current Architectural Problems

The controller layer currently performs combinations of:

```text
request parsing
inline validation
authorization checks
tenant resolution
database queries
business rules
transactions
cache operations
external integration calls
notifications
response preparation
```

This makes responsibilities difficult to locate.

---

# 7. Controller-Specific Hotspots

## 7.1 OwnerPortalController

Current approximate size:

```text
≈ 514 lines
```

It currently mixes:

```text
calendar
schedule report
schedule report export
appearance
payment settings
assets
balance
integrations
```

This is a controller aggregation problem.

These operations are not one coherent application responsibility.

---

## 7.2 OwnerBookingController

This is primarily addressed by RF-01.

RF-04 should only finalize controller responsibility after the Booking Actions exist.

Do not duplicate booking extraction work.

---

## 7.3 OwnerCheckoutController

Current responsibilities include:

```text
subscription checkout
payment creation
payment status
callback
invoice
```

Payment boundary work belongs primarily to RF-02.

RF-04 should transform the controller into an HTTP/application adapter after RF-02 boundaries are available.

---

## 7.4 OwnerDashboardController

Current approximate size:

```text
≈ 362 lines
```

It performs substantial metric queries and dashboard data construction.

The controller should eventually become:

```text
Request
 ↓
Dashboard Application Operation
 ↓
Dashboard Data
 ↓
View
```

---

## 7.5 OwnerCustomerController

Current responsibilities include:

```text
customer listing
customer detail
customer notes
```

This should become explicit application operations rather than controller-owned workflows.

---

## 7.6 OwnerProgramController

This is the legacy service-management controller.

Product terminology is:

```text
Service
```

Do not perform a global terminology migration in this phase.

However, new application classes should use canonical terminology where practical.

---

# 8. Target Architecture

Target structure:

```text
app/
├── Actions/
│   ├── Booking/
│   ├── Customer/
│   ├── Payment/
│   ├── Schedule/
│   ├── Service/
│   ├── Subscription/
│   └── Tenant/
│
├── Domain/
│   └── ...
│
└── Http/
    ├── Controllers/
    └── Requests/
```

The controller should follow:

```text
HTTP
 ↓
Request / Authorization
 ↓
Action
 ↓
Domain / Persistence
 ↓
Response
```

---

# 9. Application Action Principle

An Action represents a meaningful application operation.

Examples:

```text
CreateService
UpdateService
DeleteService

CreateSchedule
BulkCreateSchedules

CreateBooking
CreateWalkInBooking
CancelBooking
RescheduleBooking

UpdateCustomerNote

GenerateDashboardMetrics

UpdateBusinessProfile

ProcessSubscriptionPayment
```

Do not create generic classes such as:

```text
BusinessService
OwnerService
ApplicationManager
CommonAction
GeneralAction
```

without a clearly bounded responsibility.

---

# 10. Refactor Sequence

RF-04 must follow:

```text
Step 1
Application-layer baseline

↓
Step 2
Controller responsibility mapping

↓
Step 3
Form Request extraction

↓
Step 4
Owner service/application actions

↓
Step 5
Owner customer actions

↓
Step 6
Dashboard application boundary

↓
Step 7
Owner settings boundary

↓
Step 8
Owner portal decomposition

↓
Step 9
Owner checkout integration

↓
Step 10
Controller cleanup

↓
Step 11
Application-layer verification
```

Do not split every controller simultaneously.

---

# 11. Step 1 — Application Baseline

Before editing:

```text
[ ] Run full test suite
[ ] Run owner feature tests
[ ] Run customer feature tests
[ ] Run security tests
[ ] Run subscription tests
[ ] Run booking tests
[ ] Record current state
```

Relevant existing tests include:

```text
tests/Feature/Owner/OwnerModulesCrudTest.php
tests/Feature/Owner/ServicesDomainEndToEndTest.php
tests/Feature/Owner/BookingManagementTest.php
tests/Feature/Owner/CustomerManagementTest.php
tests/Feature/Owner/ScheduleManagementTest.php
tests/Feature/Owner/OwnerDashboardPollingTest.php
tests/Feature/Owner/OwnerPortalFunctionalIntegrationTest.php
tests/Feature/Owner/SettingsTest.php
tests/Feature/Owner/AnalyticsAndSubscriptionTest.php
tests/Feature/SubscriptionRulesAndMechanismsTest.php
tests/Feature/P0SecurityTest.php
```

---

# 12. Step 2 — Controller Responsibility Mapping

Before extracting anything, inspect every target controller and classify each operation as:

```text
HTTP
Validation
Authorization
Application Operation
Domain Rule
Query
Persistence
External Integration
Side Effect
Presentation Preparation
```

Produce an internal map before editing.

Do not extract blindly.

---

# 13. HTTP Responsibility

Controllers may own:

```text
route parameters
request objects
HTTP authorization boundary
invoking actions
redirects
JSON responses
view selection
flash messages
```

Controllers should not own the complete business workflow.

---

# 14. Validation Boundary

Introduce:

```text
app/Http/Requests/
```

for meaningful input contracts.

Potential areas:

```text
app/Http/Requests/Service/
app/Http/Requests/Schedule/
app/Http/Requests/Customer/
app/Http/Requests/Subscription/
app/Http/Requests/Owner/
```

Exact folder naming may be adjusted if a better coherent structure emerges.

Do not create a Form Request for trivial endpoints merely to satisfy architecture.

---

# 15. What Belongs in Form Requests

Form Requests should handle:

```text
required input
input types
formats
basic constraints
basic route/input relationships
authorization entry points where appropriate
```

They should not become the home of:

```text
complex business state
transactions
cross-module workflows
cache invalidation
notifications
payment synchronization
large database workflows
```

---

# 16. Step 3 — Service Application Operations

Current `OwnerProgramController` should gradually evolve toward:

```text
Service HTTP
 ↓
CreateService
UpdateService
DeleteService
ToggleServiceStatus
```

The current model remains:

```text
app/Models/Service.php
```

Do not rename database fields in this phase.

Do not delete `/programs` compatibility routes.

---

# 17. Service Application Rules

Preserve:

```text
tenant ownership
service activation
service deactivation
service deletion protection
category relationship
pricing
duration
staff/resource relationships
additional-item relationships
```

These rules must not be changed merely to extract Actions.

---

# 18. Step 4 — Customer Application Operations

`OwnerCustomerController` currently combines customer querying and note management.

Potential actions:

```text
GetCustomerList
GetCustomerDetail
SaveCustomerNote
```

The actual names may differ.

The important boundary is:

```text
Customer controller
        ↓
Customer application operation
```

Customer identity and tenant isolation must remain unchanged.

---

# 19. Customer Query Operations

Read operations do not necessarily require Actions.

For simple read-only operations, the controller may directly use Eloquent when that remains clear and maintainable.

Create a separate application service/query object when:

```text
query is complex
query is reused
query has meaningful business semantics
query needs independent testing
```

Do not turn every `index()` method into a class automatically.

---

# 20. Step 5 — Dashboard Application Boundary

Current dashboard logic contains multiple metrics.

Potential target:

```text
GetDashboardOverview
```

or:

```text
DashboardMetrics
```

Possible responsibilities:

```text
booking metrics
revenue metrics
customer metrics
service metrics
activity
upcoming bookings
subscription/trial indicators
```

Before extraction, verify metric definitions against `REQUIREMENTS.md`.

Do not optimize queries merely because they are long.

---

# 21. Dashboard Source of Truth

Dashboard metrics should derive from authoritative business records.

Do not introduce a new dashboard data store during RF-04.

Prefer:

```text
Booking
Payment
Customer
Service
Subscription
```

as authoritative sources.

Caching may remain an optimization.

---

# 22. Dashboard Polling

Current dashboard includes polling behavior.

The refactor must preserve:

```text
polling endpoint
response structure
refresh semantics
tenant isolation
```

Do not modify frontend polling behavior in RF-04 unless required to accommodate a changed response contract.

Presentation refactor belongs to RF-05.

---

# 23. Step 6 — Owner Settings

`OwnerSettingController` currently contains:

```text
profile
business profile
account
payment settings
payout request
account deletion
```

These are distinct operations.

Potential boundaries:

```text
UpdateBusinessProfile
UpdateOwnerAccount
UpdatePaymentSettings
RequestOwnerPayout
DeleteOwnerAccount
```

Exact names are implementation choices.

The controller should remain responsible for routing and HTTP responses.

---

# 24. Security-Sensitive Settings

The following require extra verification:

```text
account deletion
payment settings
payout
credential/configuration changes
```

Do not move logic without preserving authorization.

Especially:

```text
payment credentials
Midtrans configuration
payout information
```

must remain tenant-scoped and protected.

---

# 25. Payment Settings Boundary

Payment settings may interact with RF-02.

If RF-02 has already introduced a payment configuration/application boundary:

> reuse it.

Do not create a second payment abstraction.

If RF-02 is incomplete:

> preserve existing payment settings behavior and avoid duplicate architecture.

---

# 26. Step 7 — OwnerPortalController Decomposition

This is one of the explicit goals of RF-04.

Current responsibilities:

```text
calendar
schedule report
schedule report export
appearance
payment settings
assets
balance
integrations
```

Split by responsibility.

Potential controllers:

```text
OwnerCalendarController
OwnerScheduleReportController
OwnerAppearanceController
OwnerPaymentSettingsController
OwnerBalanceController
OwnerIntegrationController
```

These names are proposed structure, not mandatory filenames.

---

# 27. OwnerPortalController Migration Strategy

Do not delete `OwnerPortalController` first.

Use:

```text
Extract one responsibility
        ↓
Move route
        ↓
Run tests
        ↓
Verify
        ↓
Repeat
```

The recommended order:

```text
1. Calendar
2. Schedule Report
3. Appearance
4. Payment Settings
5. Balance
6. Integrations
```

This order prioritizes larger/more distinct areas first.

---

# 28. Calendar

Calendar functionality should eventually have:

```text
HTTP controller
+
calendar query/application operation
+
calendar view
```

Do not move calendar UI refactoring into this phase.

RF-05 handles Blade decomposition.

The application layer only prepares and retrieves the necessary data.

---

# 29. Schedule Report

Schedule report generation and export should be separated from the generic portal controller.

Potential operations:

```text
GenerateScheduleReport
ExportScheduleReport
```

The exported data format is product behavior.

Do not modify it during architectural extraction.

---

# 30. Appearance

Appearance management should be isolated.

Potential application operations:

```text
GetAppearanceSettings
UpdateAppearanceSettings
```

Preserve:

```text
tenant ownership
asset/path behavior
branding fields
landing-page behavior
```

Do not redesign appearance UI.

---

# 31. Assets

Asset management already has its own controller:

```text
OwnerAssetController
```

Do not duplicate it into the new portal architecture.

RF-04 should instead verify that `OwnerPortalController::assets()` is not creating a second asset workflow.

If an existing dedicated controller already owns the capability:

> route future behavior toward that owner rather than introducing another implementation.

---

# 32. Balance

Balance and payout concepts should not be confused with generic settings.

If balance is read-only:

```text
query/application boundary
```

may be sufficient.

If future behavior requires mutation:

```text
explicit financial action
```

should be used.

Do not invent payout/accounting capabilities.

---

# 33. Integrations

`integrations()` should remain a bounded presentation/application concern.

Do not create integration adapters merely because the page exists.

RF-04 should only clarify the existing application boundary.

Actual external integration work belongs to the relevant domain or future feature work.

---

# 34. Step 8 — Owner Checkout

Owner checkout overlaps RF-02.

RF-04 responsibility:

```text
HTTP controller
        ↓
payment application operation
```

RF-02 responsibility:

```text
payment domain/provider boundary
```

Do not implement two parallel payment workflows.

---

# 35. Controller Dependency Rule

Controllers should not accumulate:

```text
20+ model imports
multiple external SDKs
complex transaction closures
large cache logic
large validation blocks
```

If a controller still has substantial business logic after extraction:

> identify the specific responsibility that remains and move that responsibility to its proper owner.

Do not simply rename private methods into services without clarifying their responsibility.

---

# 36. Shared Tenant Resolution

Current owner controllers use:

```text
ResolvesOwnerTenant
TenantContext
auth()->user()
```

RF-04 must reduce duplicated tenant-resolution behavior.

Preferred:

```text
middleware/context
        ↓
authorized owner tenant
        ↓
application operation
```

Do not introduce a second tenant-resolution mechanism.

---

# 37. Tenant Security

Every Action must verify or operate within the proper tenant context.

Do not assume:

```text
$user->tenant
```

alone is sufficient for every operation.

Do not accept owner-controlled IDs without checking ownership.

Examples:

```text
service_id
customer_id
schedule_id
booking_id
staff_id
resource_id
voucher_id
asset_id
```

---

# 38. Authorization Boundary

Do not move authorization into the frontend.

Authorization must remain server-side.

Preferred conceptual flow:

```text
Request
 ↓
Authorization
 ↓
Action
```

An Action may also perform business-level ownership validation where required.

---

# 39. Action Transaction Boundary

Actions should own a transaction when the operation modifies multiple related records and partial completion would be invalid.

Examples:

```text
create complex service data
bulk schedule creation
critical settings update
complex customer mutation
```

Do not wrap every Action in a transaction.

---

# 40. Notifications and Side Effects

Actions may coordinate required side effects.

However, do not move every mail or notification call into generic application classes.

For each side effect, identify:

```text
what triggered it
whether it is required
whether it is domain/application behavior
whether existing implementation already owns it
```

Do not introduce queues/events solely to make architecture appear sophisticated.

---

# 41. Action Naming

Prefer verbs describing business operations.

Good:

```text
CreateService
UpdateService
DeleteService
ToggleServiceStatus

SaveCustomerNote

GenerateDashboardMetrics

UpdateBusinessProfile
RequestOwnerPayout
DeleteOwnerAccount
```

Avoid:

```text
ServiceManager
OwnerHelper
BusinessProcessor
CommonService
OwnerUtility
```

---

# 42. Read vs Write Operations

Do not force all application behavior into Actions.

Recommended approach:

```text
Write / command
→ Action

Complex read
→ Query/Application service when useful

Simple read
→ Direct Eloquent is acceptable
```

The architecture should optimize for clarity, not number of classes.

---

# 43. Form Request Strategy

Create Form Requests only where validation logic is currently:

```text
large
repeated
meaningful
security-sensitive
difficult to test
```

Likely high-value Requests include:

```text
CreateServiceRequest
UpdateServiceRequest

CreateScheduleRequest
BulkCreateScheduleRequest

UpdateBusinessProfileRequest
UpdateOwnerAccountRequest

SaveCustomerNoteRequest
```

Booking-specific Requests are handled by RF-01.

Payment-specific Requests are handled by RF-02.

---

# 44. Database Safety

RF-04 must not perform database-wide naming migrations.

Do not rename:

```text
idtenant
idlayanan
namalayanan
namabisnis
tanggalbooking
```

as part of controller refactoring.

New PHP code should use canonical terminology where possible, but compatibility with the existing schema must remain.

---

# 45. Files / Areas In Scope

Primary controllers:

```text
app/Http/Controllers/Owner/OwnerPortalController.php
app/Http/Controllers/Owner/OwnerDashboardController.php
app/Http/Controllers/Owner/OwnerCustomerController.php
app/Http/Controllers/Owner/OwnerProgramController.php
app/Http/Controllers/Owner/OwnerSettingController.php
app/Http/Controllers/Owner/OwnerCheckoutController.php
app/Http/Controllers/Owner/OwnerScheduleController.php
app/Http/Controllers/Owner/OwnerBookingController.php
```

Additional controllers may be touched only when required to complete the application boundary.

Target locations:

```text
app/Actions/*
app/Http/Requests/*
```

Relevant tests may be updated or added.

---

# 46. Restricted Areas

Do not use RF-04 to:

```text
redesign Blade UI
rename database schema
replace Eloquent
rewrite tenant architecture
rewrite payment provider
rewrite booking domain
implement new product features
remove compatibility routes
```

Do not make unrelated cleanup merely because the affected controller contains it.

---

# 47. Forbidden Changes

Do not:

```text
create Action classes for every method automatically
create repositories for every model
create interfaces for every Action
move all logic into generic services
create a generic OwnerService
move business rules into Blade
move authorization to JavaScript
duplicate booking/payment/schedule rules
change user-visible behavior without requirement
```

---

# 48. Testing Strategy

Each extracted responsibility must retain its existing behavior.

At minimum run:

```text
owner module CRUD tests
owner booking tests
owner schedule tests
owner customer tests
owner dashboard tests
owner settings tests
owner portal tests
service end-to-end tests
subscription tests
security tests
booking regression tests
```

When `OwnerPortalController` is split, the portal integration test must remain green.

---

# 49. Controller Decomposition Testing

When moving a route from:

```text
OwnerPortalController
```

to a dedicated controller:

Verify:

```text
route name
HTTP method
middleware
authorization
tenant context
response/view
parameters
status codes
redirect behavior
```

Do not treat controller relocation as sufficient verification.

---

# 50. Acceptance Criteria

RF-04 is complete when:

```text
[ ] Major owner application workflows have explicit owners
[ ] Controllers are primarily HTTP adapters
[ ] Important validation is extracted into appropriate Requests
[ ] Booking workflows reuse RF-01 operations where available
[ ] Payment workflows reuse RF-02 boundaries where available
[ ] Schedule workflows reuse RF-03 operations where available
[ ] OwnerPortalController is decomposed
[ ] Dashboard data construction has a clear application boundary
[ ] Owner settings operations have clear boundaries
[ ] Customer management operations have clear boundaries
[ ] Service management has clear application boundaries
[ ] Tenant resolution is not duplicated unnecessarily
[ ] Authorization remains server-side
[ ] Existing tests pass
[ ] No unrelated product behavior changed
[ ] TRACKER.md is updated
[ ] ARCHITECTURE.md reflects meaningful structural changes
```

---

# 51. Quality Criteria

This is not sufficient:

```text
"The controller became smaller."
```

The improvement must be:

```text
Controller
→ clear HTTP responsibility

Action
→ clear application responsibility

Domain
→ clear business rule responsibility

Model
→ clear persistence/entity responsibility
```

A method moved into a new class without clarifying its ownership does not constitute a successful application-layer refactor.

---

# 52. Stop Conditions

Stop and report if:

```text
an extracted Action would redefine product behavior
a controller depends on an ambiguous domain rule
authorization behavior is unclear
tenant ownership is unclear
RF-01/RF-02/RF-03 boundaries conflict
a database migration appears necessary
a controller split would break compatibility behavior
the same business operation appears to have multiple contradictory implementations
```

Do not choose silently between conflicting domain interpretations.

---

# 53. Dependency Rule

RF-04 must use existing completed boundaries when available.

For example:

```text
RF-01 complete
        ↓
OwnerBookingController
        ↓
Booking Actions
```

not:

```text
RF-04
        ↓
new booking logic
```

Likewise:

```text
RF-02 payment boundary
        ↓
OwnerCheckoutController
```

and:

```text
RF-03 schedule availability
        ↓
OwnerScheduleController
```

Avoid rebuilding logic that another work order already owns.

---

# 54. Completion Report

When RF-04 is complete, report:

```text
RF:
RF-04-APPLICATION-LAYER

Branch:
Refactor

Baseline Commit:
ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8

Current Commit:
<commit>

Actions Created:
<list>

Requests Created:
<list>

Controllers Split:
<list>

Controllers Reduced:
<list>

Controllers Removed:
<list>

Queries / Services Created:
<list>

Tests Added:
<list>

Tests Executed:
<list>

Test Result:
<result>

Requirements Covered:
<IDs>

Dependencies Used:
RF-01:
RF-02:
RF-03:

Behavior Changes:
None, unless explicitly documented.

Remaining Application-Layer Debt:
<list>

Tracker Updated:
Yes/No

Architecture Documentation Updated:
Yes/No

Blockers:
<list>
```

---

# 55. Final Rule

RF-04 must move BookQu toward:

```text
Thin Controllers
      ↓
Explicit Application Operations
      ↓
Clear Domain Ownership
      ↓
Reusable Business Rules
      ↓
Testable Implementation
```

It must remain aligned with:

```text
PRODUCT.md
REQUIREMENTS.md
ARCHITECTURE.md
DEVELOPMENT.md
TRACKER.md
REFACTOR-PLAN.md
```

and the current `Refactor` branch baseline:

```text
ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8
```

No controller decomposition may silently introduce, remove, or redefine product behavior.
