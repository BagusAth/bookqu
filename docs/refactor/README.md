# BookQu Refactor Work Orders

This directory contains executable refactor instructions for AI agents.

## How To Use

Use exactly one work-order document per agent task.

Recommended sequence:

```text
docs/REFACTOR-PLAN.md
      ↓
Select one RF document
      ↓
Read AGENT.md
      ↓
Read relevant docs/2-PRODUCT.md / docs/3-REQUIREMENT.md / docs/4-ARCHITECTURE.md sections
      ↓
Read the complete RF document
      ↓
Execute its subtask sequence
      ↓
Test after each major subtask
      ↓
Check acceptance criteria
      ↓
Update docs/6-TRACKER.md
```

## Work Orders (RF-00 through RF-09 — Completed)

| Work Order | Title | Scope | Status |
|:---|:---|:---|:---:|
| [RF-00-FOUNDATION.md](file:///c:/laragon/www/bookqu/docs/refactor/RF-00-FOUNDATION.md) | Test Suite Baseline & Safety Net | Test baseline, factories, test runners | Completed |
| [RF-01-BOOKING.md](file:///c:/laragon/www/bookqu/docs/refactor/RF-01-BOOKING.md) | Booking Logic Decoupling | `CreateBooking`, domain state, slot concurrency | Completed |
| [RF-02-PAYMENT.md](file:///c:/laragon/www/bookqu/docs/refactor/RF-02-PAYMENT.md) | Payment & Midtrans Decoupling | `ProcessPaymentWebhook`, Midtrans client isolation | Completed |
| [RF-03-SCHEDULE.md](file:///c:/laragon/www/bookqu/docs/refactor/RF-03-SCHEDULE.md) | Schedule & Availability Engine | `ScheduleSlotGenerator`, slot caching, tenant safety | Completed |
| [RF-04-AUTH-TENANT.md](file:///c:/laragon/www/bookqu/docs/refactor/RF-04-AUTH-TENANT.md) | Auth & Tenant Isolation Hardening | Global tenant scope, policies, auth controllers | Completed |
| [RF-05-VIEW-DECOMPOSITION.md](file:///c:/laragon/www/bookqu/docs/refactor/RF-05-VIEW-DECOMPOSITION.md) | View Decomposition & Partials | Customer & owner blade componentization | Completed |
| [RF-06-FORM-REQUESTS.md](file:///c:/laragon/www/bookqu/docs/refactor/RF-06-FORM-REQUESTS.md) | Request Validation Extraction | Form requests across booking, schedule, tenant | Completed |
| [RF-07-DATABASE-NAMING.md](file:///c:/laragon/www/bookqu/docs/refactor/RF-07-DATABASE-NAMING.md) | DB Invariants & Virtual Column | Slot concurrency unique key, active schedule col | Completed |
| [RF-08-FINAL-AUDIT.md](file:///c:/laragon/www/bookqu/docs/refactor/RF-08-FINAL-AUDIT.md) | Final Architecture Audit | Codebase audit across all layers | Completed |
| [RF-09-HARDENING.md](file:///c:/laragon/www/bookqu/docs/refactor/RF-09-HARDENING.md) | Comprehensive System Hardening | Payment expiry, race conditions, 315 tests | Completed |

## Work-Order Contract

Every RF document defines:

* objective;
* current implementation;
* authoritative requirements;
* target architecture;
* exact scope;
* subtask sequence;
* expected solution direction;
* files/areas that may be changed;
* files/areas that must not be changed;
* tests required;
* acceptance criteria;
* forbidden assumptions;
* completion report format.

## Agent Rule

The RF document is not permission to invent behavior.

If implementation details are unclear but product behavior is already defined, the agent should inspect the current implementation/tests and choose the smallest change consistent with the RF instructions.

If a choice would materially change product behavior, status semantics, security, database meaning, or external payment behavior, stop and report the conflict instead of inventing a solution.

## Work-Order Status

```text
Planned
In Progress
Testing
Done
Blocked
Needs Revision
```

Status changes belong in `docs/6-TRACKER.md`; the RF file remains the stable instructions for the phase.
