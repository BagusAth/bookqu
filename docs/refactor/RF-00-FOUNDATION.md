# RF-00 — Foundation & Alignment

> **Status:** Completed
> **Priority:** Critical
> **Phase:** Foundation & Documentation Alignment
> **Integration Branch:** `Refactor`
> **Baseline Commit:** `ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8`
> **Reference Implementation:** `mergeV2`
> **Depends On:** None (Root Work Order)
> **Purpose:** Establish the architectural foundation, canonical documentation system, characterization test baseline, and work-order progression structure for the BookQu architecture refactor.

---

# 1. Objective

Establish an unshakeable foundation before executing major domain refactoring on BookQu:

1. Canonical documentation architecture (`AGENT.md`, `docs/1-README.md` through `docs/6-TRACKER.md`, `docs/REFACTOR-PLAN.md`, `docs/refactor/RF-*.md`).
2. Verification and preservation of initial test suite baseline (all functional and security tests).
3. Establishing strict non-negotiable refactoring invariants:
   - Zero database schema breakage (preserve Indonesian column names).
   - Zero product regression.
   - Fail-closed tenant isolation (`TenantScope`).
   - Idempotent payment webhooks with Midtrans signatures.
   - Concurrency locking on booking schedules.
   - Backward-compatible routes and legacy tokenized guest URLs.

---

# 2. Scope of Foundation

```text
Foundation Deliverables
├── Documentation System
│   ├── AGENT.md
│   ├── docs/1-README.md
│   ├── docs/2-PRODUCT.md
│   ├── docs/3-REQUIREMENT.md
│   ├── docs/4-ARCHITECTURE.md
│   ├── docs/5-DEVELOPMENT.md
│   ├── docs/6-TRACKER.md
│   └── docs/REFACTOR-PLAN.md
│
├── Work-Order System (docs/refactor/)
│   ├── RF-00-FOUNDATION.md
│   ├── RF-01-BOOKING.md
│   ├── RF-02-PAYMENT.md
│   ├── RF-03-SCHEDULE.md
│   ├── RF-04-APPLICATION-LAYER.md
│   ├── RF-05-PRESENTATION.md
│   ├── RF-06-SUBSCRIPTION.md
│   ├── RF-07-CLEANUP-ALIGNMENT.md
│   └── RF-08-FINAL-AUDIT.md
│
└── Test Baseline & Characterization
    └── php artisan test baseline recording (301 tests passing)
```

---

# 3. Work Order Execution Sequence

The refactoring roadmap is executed strictly in bounded, test-gated phases:

| Work Order | Domain / Area | Goal | Status |
| :--- | :--- | :--- | :--- |
| **RF-00** | **Foundation** | Documentation baseline, test baseline, work order structure | **Completed** |
| **RF-01** | **Booking** | Extract booking actions, domain rules (`BookingState`, `BookingRules`), form requests | **Completed** |
| **RF-02** | **Payment** | Payment gateway abstraction (`MidtransPaymentGateway`), payment synchronization actions, payment state machine | **Completed** |
| **RF-03** | **Schedule** | Schedule actions, availability domain rules, conflict detection, `ScheduleAvailabilityCache` | **Completed** |
| **RF-04** | **Application Layer** | 17 domain actions across modules, decompose `OwnerPortalController`, form requests | **Completed** |
| **RF-05** | **Presentation** | Modularize monolithic Blade views into cohesive partials (`owner/partials/*`, `customer/partials/*`) | **Completed** |
| **RF-06** | **Subscription** | `SubscriptionState`, `PlanCapability`, `EntitlementRules`, `SubscriptionUsage`, subscription actions | **Completed** |
| **RF-07** | **Cleanup & Alignment** | Route closure extraction into Auth controllers, form requests, dead code removal | **Completed** |
| **RF-08** | **Final Audit** | Complete multi-dimensional audit, cross-domain integrity verification, sign-off | **Completed** |

---

# 4. Acceptance Criteria & Invariants

- [x] Canonical documentation exists and references match.
- [x] Requirement and Architecture baseline exists.
- [x] Initial test baseline recorded with 100% green status.
- [x] Refactor work orders defined with clear non-overlapping scopes.
- [x] Git branch strategy uses `Refactor` as the integration branch.
- [x] Characterization strategy preserves existing business behavior without regressions.

---

# 5. Sign-Off

- **Phase:** RF-00 Foundation
- **Result:** PASS
- **Baseline Established:** 2026-09-26
