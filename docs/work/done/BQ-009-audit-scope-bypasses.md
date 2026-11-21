---
id: BQ-009
title: Audit every withoutGlobalScope(s) usage
type: improvement
status: done
priority: P1
phase: 4
domain: tenancy
requirements: [FR-TENANT-002, NFR-SEC-007]
invariants: [INV-TEN-01]
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-009: Audit every withoutGlobalScope(s) usage

Classification rule decided in [ADR-0010](../../adr/ADR-0010-authorization-and-tenant-scoping.md) (decision 5: four allowed places, tenant-context runner elsewhere), 2026-10-30.

## Problem

There are 55 calls of `withoutGlobalScope(s)` in 29 files (largest: `AdminDashboardController` 7, `Service` 4, `SynchronizePaymentStatus` 4). Spot checks (`UpdateService`, `DeleteService`, `DeleteSchedule`) re-filter by tenant, but no inventory proves each one is safe (remaining item 1 of the 2026-10-17 technical audit).

## Plan

List each call with file:line, reason and ownership check; fix or justify; add a short comment at each call; add tests where a bypass guards data access. A parallel (ultracode) review fits this item.

## Evidence

Done 2026-11-01. Reviewed all 40 unexplained `withoutGlobalScope(s)` calls in 22 files. Every one either re-filters by the authenticated or resolved tenant, or is cross-tenant by design (console jobs, `role:admin` dashboard, webhook lookup before tenant derivation). None needed a code fix. Each call now carries a `// scope-bypass:` reason, and the `scope-bypass-reason` allowlist is empty, so `ArchitectureRulesTest` blocks new unexplained bypasses.

## Follow-up enforcement

The original audit documented reasons for individual calls. ADR-0010 now needs a checked boundary for where those calls can occur.

The Tech Lead approved the follow-up in the task instructions on 2026-11-21.

### Plan

Use a tenant-context runner for trusted origins outside the request. Remove redundant bypasses, keep exact group membership checks, and allow only reviewed admin reads, tenant discovery, platform candidate scans, and grant resolution. Verify owner, customer, payment, admin and background paths before and after changes.

### Inventory

This snapshot records 122 calls in 73 files on `origin/integration` at `9176fbf`. The safety column distinguishes explicit ownership checks, the approved cross-tenant boundaries, and places where the caller or relation requires closer verification.

| File and line | Reason | Safe? | Action |
|---|---|---|---|
| `app/Actions/Admin/ProcessOwnerPayout.php:37` | platform admin (role:admin route) reviews payouts of every tenant | Trusted identity lookup | Keep only the lookup before establishing context |
| `app/Actions/Admin/ProcessOwnerPayout.php:45` | platform admin, payout re-read under the tenant lock | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Booking/BookingPricingReservations.php:43` | settlement uses the stored payment tenant, including synchronous placement. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Booking/BookingPricingReservations.php:47` | settlement uses the stored payment tenant, including synchronous placement. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Booking/CancelBooking.php:90` | payment group re-filtered by the booking's own idtenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Booking/CreateWalkInBooking.php:56` | only to read the schedule's service; ClaimSlots verifies tenant ownership under lock. | Parent/subject consistency needs a check | Scope to the verified tenant and test a foreign reference |
| `app/Actions/Booking/EvictStalePendingBookings.php:44` | schedule ids come from the caller's tenant-locked schedules | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Booking/EvictStalePendingBookings.php:57` | the payment of a booking already verified to belong to the claimed schedules. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Booking/GetBookingTimeRanges.php:27` | the slots of bookings the caller already authorized, filtered to each booking's tenant. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Booking/ListOwnerBookings.php:38` | the stream runs after middleware clears context, and every row is filtered to the authenticated tenant below. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Actions/Booking/MarkBookingNoShow.php:59` | members of this booking's own payment group, filtered by its tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Booking/QuoteBooking.php:35` | a checkout quote only looks up codes within the resolved tenant. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Booking/QuoteBooking.php:45` | selected items must belong to the tenant and be linked to this service. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Category/DeleteCategory.php:32` | filtered by the authenticated tenant; inactive services count as members too | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/CustomerAccess/DeliverManagementAccess.php:21` | trusted queue carries only a delivery id; validate its tenant/family under lock before minting. | Trusted identity lookup | Keep only the lookup before establishing context |
| `app/Actions/CustomerAccess/IssueCustomerAccess.php:27` | trusted booking subject; payment must belong to that same tenant. | Must detect corrupt group membership | Centralize exact-member read in the customer-access resolver |
| `app/Actions/CustomerAccess/IssueCustomerAccess.php:32` | validate the full group, including any foreign-tenant membership, before issuance. | Must detect corrupt group membership | Centralize exact-member read in the customer-access resolver |
| `app/Actions/CustomerAccess/LoadAccessSubject.php:19` | opaque grant lookup precedes tenant middleware; constrain by the stored grant tenant. | Exact grant or member validation | Keep in the customer-access resolver |
| `app/Actions/CustomerAccess/LoadAccessSubject.php:22` | inspect the entire bound group, including corrupt foreign members, before authorizing it. | Exact grant or member validation | Keep in the customer-access resolver |
| `app/Actions/CustomerAccess/LoadAccessSubject.php:26` | standalone subject is constrained to the immutable grant tenant and booking id. | Exact grant or member validation | Keep in the customer-access resolver |
| `app/Actions/CustomerAccess/RequestManagementDelivery.php:23` | trusted business booking; require payment ownership before creating delivery intent. | Must detect corrupt group membership | Centralize exact-member read in the customer-access resolver |
| `app/Actions/CustomerAccess/RequestManagementDelivery.php:27` | complete group snapshot is checked again by the delivery handler before minting. | Must detect corrupt group membership | Centralize exact-member read in the customer-access resolver |
| `app/Actions/CustomerAccess/ResolveCustomerAccess.php:21` | selector bootstraps identity; subject and any existing tenant context are checked next. | Exact grant or member validation | Keep in the customer-access resolver |
| `app/Actions/CustomerAccess/ResolveCustomerAccess.php:30` | server-side session references a grant; verify tenant against its stored subject. | Exact grant or member validation | Keep in the customer-access resolver |
| `app/Actions/Owner/DeleteOwnerAccount.php:50` | verify in-flight payouts for explicit tenant before account deletion | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Owner/GetOwnerShell.php:50` | the subscription of the signed-in member's own business | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Owner/GetOwnerShell.php:137` | open refund requests of the member's own business | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Owner/GetOwnerShell.php:149` | reviews of the member's own business | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Owner/ListWalkInSlots.php:27` | filtered by the server-resolved tenant of the signed-in member | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Owner/RequestOwnerPayout.php:84` | count today's payouts for locked tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Owner/RequestOwnerPayout.php:98` | load payout account for locked tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Owner/SearchOwnerRecords.php:39` | filtered by the server-resolved tenant of the signed-in member | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Owner/TenantBalanceLedger.php:25` | the payment is already resolved and locked by the lifecycle | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Payment/Lifecycle/BookingGroupConsequences.php:125` | the group is resolved by payment id; bookings of another tenant are filtered out below. | Parent/subject consistency needs a check | Scope to the verified tenant and test a foreign reference |
| `app/Actions/Payment/PaymentLifecycle.php:151` | the payment is resolved by the booking's id; tenant equality is checked under lock. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Actions/Payment/PaymentLifecycle.php:227` | alerts run after commit, outside any request tenant context. | Tenant model is unscoped | Remove redundant bypass |
| `app/Actions/Payment/PaymentLifecycle.php:277` | tenant-first lock on the payment's stored tenant (ADR-0005 decision 4). | Tenant model is unscoped | Remove redundant bypass |
| `app/Actions/Payment/PaymentLifecycle.php:281` | webhooks and commands have no tenant context; the tenant comes from the stored row. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Actions/Payment/PaymentLifecycle.php:296` | the stored tenant of the payment decides the lock and the context. | Trusted identity lookup | Keep only the lookup before establishing context |
| `app/Actions/Payment/PaymentOutcomeIntake.php:128` | the tenant is the payment's stored tenant; webhooks carry no tenant context. | Tenant model is unscoped | Remove redundant bypass |
| `app/Actions/Payment/PaymentOutcomeIntake.php:155` | provider outcomes arrive without tenant context; ownership is validated next. | Trusted identity lookup | Keep only the lookup before establishing context |
| `app/Actions/Payment/ProcessPaymentWebhook.php:62` | webhooks carry no tenant context; the tenant is derived from the payment before any write | Trusted identity lookup | Keep only the lookup before establishing context |
| `app/Actions/Payment/ProcessRefundTransfer.php:19` | platform admin processes the refund's stored payment; recheck under the tenant lock. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Payment/RemindOpenRefundRequests.php:18` | scheduler enumerates only identifiers; each request is rechecked under its stored tenant lock. | Cross-tenant candidate selection | Keep discovery and move candidate work into the runner |
| `app/Actions/Payment/RemindOpenRefundRequests.php:25` | scheduler rechecks the same request inside its tenant lock. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Actions/Schedule/ApplyBasePriceToOpenSlots.php:38` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/ApplyBasePriceToOpenSlots.php:53` | ids selected above for the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/BulkCreateSchedules.php:85` | every row carries the authenticated tenant's idtenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/BulkCreateSchedules.php:136` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/BulkCreateSchedules.php:167` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/ClaimSlots.php:59` | ownership of every requested schedule is verified below, before any cleanup. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/ClaimSlots.php:121` | the tenant row itself is the lock; the caller resolved the tenant server-side. | Tenant model is unscoped | Remove redundant bypass |
| `app/Actions/Schedule/DeleteBlockedDate.php:28` | filtered by the authenticated tenant and re-checked below | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/DeleteSchedule.php:32` | filtered by the authenticated tenant and re-checked below | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/GetAvailableSchedules.php:258` | filtered by the resolved storefront tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/GetAvailableSchedules.php:296` | the booking being rescheduled, already resolved for this tenant | Parent/subject consistency needs a check | Scope to the verified tenant and test a foreign reference |
| `app/Actions/Schedule/GetSlotRunway.php:34` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/SaveOpeningHours.php:42` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/SavePriceRules.php:75` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/SavePriceRules.php:107` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/SimulateSlotPrices.php:35` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Schedule/UpdateScheduleAvailability.php:58` | the record is keyed by the server-resolved tenant. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Service/DeleteService.php:32` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Service/ToggleServiceStatus.php:27` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Service/UpdateService.php:37` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Subscription/ActivateSubscription.php:20` | payment tenant is stored trusted provider evidence; keep the same tenant under lock. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Actions/Subscription/ActivateSubscription.php:25` | activation identity belongs only to this verified payment and stored tenant. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Actions/Subscription/ActivateSubscription.php:30` | renewal reads only the verified payment tenant under its tenant-first lock. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Actions/Subscription/ActivateSubscription.php:33` | only older periods of this verified payment tenant are superseded. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Actions/Subscription/CreateTrialSubscription.php:27` | trial issuance is tied to the stored owning account and locked tenant. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Actions/Subscription/GetSubscriptionOverview.php:69` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Subscription/GetSubscriptionOverview.php:97` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Subscription/GetSubscriptionOverview.php:106` | members of the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Subscription/SendSubscriptionReminders.php:20` | scheduled platform sweep across tenants; each candidate is revalidated under its tenant lock. | Cross-tenant candidate selection | Keep discovery and move candidate work into the runner |
| `app/Actions/Subscription/SendSubscriptionReminders.php:33` | candidate belongs to this stored tenant, reloaded under the tenant-first lock. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Actions/Team/CreateOwnerMembership.php:13` | creation establishes the owning membership for this newly stored tenant, before request context exists. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Team/ManageTeam.php:94` | invitation acceptance checks every membership of this verified user before choosing a tenant. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Team/ManageTeam.php:159` | public acceptance reads only the subscription of the locked invitation tenant. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Team/ResolveTenantMembership.php:14` | authentication resolves tenancy from the trusted signed-in user, before tenant context exists. | Trusted identity lookup | Keep only the lookup before establishing context |
| `app/Actions/Team/ResolveTenantMembership.php:29` | authentication checks only memberships belonging to this signed-in user. | Trusted identity lookup | Keep only the lookup before establishing context |
| `app/Actions/Unit/AssignServiceUnits.php:53` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Unit/GetUnitsOverview.php:30` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Actions/Unit/SaveUnit.php:99` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Console/Commands/CheckExpiredSubscriptions.php:23` | scheduled platform sweep; statuses are tidied only through SubscriptionState's date and transition rules. | Cross-tenant candidate selection | Keep discovery and move candidate work into the runner |
| `app/Console/Commands/ExpireBookingPayments.php:33` | the sweep covers every tenant; each payment is reclaimed in its own tenant-locked transaction. | Cross-tenant candidate selection | Keep discovery and move candidate work into the runner |
| `app/Console/Commands/ExpireBookingPayments.php:57` | bookings of one payment; tenant context set from that payment | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Console/Commands/GenerateTestBookingToken.php:25` | explicit local diagnostic selection; no production access and issuance validates the subject. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Console/Commands/SimulateSingaPayWebhook.php:44` | a console command has no tenant context; the payment is looked up by its order id only. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Domain/Payout/PayoutEligibility.php:54` | eligibility evaluates counts for the explicitly provided tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Domain/Payout/PayoutEligibility.php:65` | eligibility evaluates accounts for the explicitly provided tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Domain/Schedule/AvailabilityRules.php:98` | the schedule's own service, read for eligibility only. | Parent/subject consistency needs a check | Scope to the verified tenant and test a foreign reference |
| `app/Domain/Schedule/AvailabilityRules.php:122` | filtered by the given tenant id | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Domain/Schedule/PriceRules.php:43` | filtered by the given tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Domain/Schedule/PriceRules.php:54` | filtered by the given tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Domain/Schedule/ScheduleConflictRules.php:27` | filtered by the given tenant id or an already-resolved schedule id | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Domain/Schedule/ScheduleConflictRules.php:55` | filtered by the given tenant id | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Domain/Schedule/ScheduleConflictRules.php:93` | filtered by the given, server-resolved tenant id | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Domain/Schedule/ScheduleConflictRules.php:94` | filtered by the given, server-resolved tenant id | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Domain/Subscription/SubscriptionUsage.php:32` | filtered by the given tenant id | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Domain/Subscription/SubscriptionUsage.php:43` | filtered by the given tenant id | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Http/Controllers/Admin/AdminDashboardController.php:21` | platform admin aggregates (role:admin route) | Platform-admin read | Keep on the explicit admin read allowlist |
| `app/Http/Controllers/Admin/AdminDashboardController.php:22` | platform admin aggregates (role:admin route) | Platform-admin read | Keep on the explicit admin read allowlist |
| `app/Http/Controllers/Admin/AdminDashboardController.php:24` | platform admin aggregates (role:admin route) | Platform-admin read | Keep on the explicit admin read allowlist |
| `app/Http/Controllers/Admin/AdminDashboardController.php:27` | platform admin aggregates pending payouts count | Platform-admin read | Keep on the explicit admin read allowlist |
| `app/Http/Controllers/Admin/AdminDashboardController.php:31` | platform admin aggregates (role:admin route) | Platform-admin read | Keep on the explicit admin read allowlist |
| `app/Http/Controllers/Admin/AdminDashboardController.php:36` | platform admin aggregates (role:admin route) | Platform-admin read | Keep on the explicit admin read allowlist |
| `app/Http/Controllers/Admin/AdminDashboardController.php:45` | platform admin aggregates (role:admin route) | Platform-admin read | Keep on the explicit admin read allowlist |
| `app/Http/Controllers/Admin/AdminDashboardController.php:59` | platform admin aggregates (role:admin route) | Platform-admin read | Keep on the explicit admin read allowlist |
| `app/Http/Controllers/Admin/AdminPayoutController.php:20` | platform admin (role:admin route) lists payouts of every tenant | Platform-admin read | Keep on the explicit admin read allowlist |
| `app/Http/Controllers/Customer/StorefrontBookingController.php:39` | filtered by the resolved storefront tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Http/Controllers/Owner/OwnerPriceRuleController.php:119` | filtered by the authenticated tenant | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Http/Middleware/AuthenticateOwnerApiKey.php:32` | a verified digest identifies the tenant for this stateless API request. | Trusted identity lookup | Keep only the lookup before establishing context |
| `app/Jobs/SendRefundCustomerUpdate.php:19` | a queued notification is bound to its stored booking and tenant. | Context absent at this boundary | Use a scoped runner with a trusted tenant |
| `app/Models/Booking.php:74` | resolve the stored service relation; booking writes verify service and tenant ownership. | Parent/subject consistency needs a check | Scope to the verified tenant and test a foreign reference |
| `app/Models/Booking.php:85` | resolve the stored payment relation; customer grants validate the complete payment family. | Parent/subject consistency needs a check | Scope to the verified tenant and test a foreign reference |
| `app/Models/Booking.php:150` | count members of the stored payment group; callers authorize the booking before using this refusal. | Parent/subject consistency needs a check | Scope to the verified tenant and test a foreign reference |
| `app/Models/OwnerPayout.php:51` | payout references its own snapshot account row regardless of current tenant context | Parent/subject consistency needs a check | Scope to the verified tenant and test a foreign reference |
| `app/Models/Payment.php:109` | route binding resolves a public reference; customer middleware and controllers verify grant and tenant ownership. | Trusted identity lookup | Keep only the lookup before establishing context |
| `app/Models/Refund.php:43` | admin reads the request of this refund by its stored foreign key. | Parent/subject consistency needs a check | Scope to the verified tenant and test a foreign reference |
| `app/Providers/AppServiceProvider.php:102` | an after-commit event contains the tenant of the saved booking. | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Services/Schedule/ScheduleAvailabilityCache.php:92` | filtered by the given tenant id | Tenant-qualified or checked by caller | Use the tenant scope |
| `app/Support/TenantContext.php:40` | resolves the context tenant itself by id | Tenant model is unscoped | Remove redundant bypass |

### Follow-up evidence

Initial characterization on the base branch passed (162 tests, 1,326 assertions), covering team permissions, access grants, payments, owner payouts and console commands.
