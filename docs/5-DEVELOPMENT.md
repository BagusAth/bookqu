# BookQu Development Guide

> **Document Status:** Current Development Standard
> **Version:** 1.0
> **Authority:** Authoritative development workflow
> **Product Definition:** `docs/PRODUCT.md`
> **Requirements:** `docs/REQUIREMENTS.md`
> **Architecture:** `docs/ARCHITECTURE.md`
> **Tracker:** `docs/TRACKER.md`
> **Agent Rules:** `AGENTS.md`
> **Last Updated:** 2026-09-26
>
> This document defines how BookQu is developed, changed, tested, refactored, and reviewed.
>
> The purpose of this document is to ensure that every developer and AI agent follows the same development process.

---

# 1. Purpose

BookQu development follows a requirement-driven workflow.

The development process must prevent:

* undocumented feature additions;
* silent scope expansion;
* duplicated business logic;
* uncontrolled architectural drift;
* implementation based on guesswork;
* refactoring that accidentally changes product behavior;
* inconsistent work between developers and AI agents;
* code that works but cannot be maintained.

The primary development principle is:

> **Do not let source code silently redefine the product.**

Product behavior must be defined first.

Implementation follows the defined behavior.

---

# 2. Development Principles

BookQu development follows these principles.

## 2.1 Requirement First

Every meaningful product behavior must have an identifiable requirement.

```text id="p2v3qz"
Requirement
    ↓
Design / Technical Approach
    ↓
Implementation
    ↓
Test
```

Do not begin implementation by modifying code before identifying the relevant requirement.

---

## 2.2 Existing Code Is Evidence, Not Automatically the Standard

The current codebase contains both good patterns and technical debt.

Therefore:

> Existing code shows what BookQu currently does; it does not automatically define how new code should be written.

New code should follow `ARCHITECTURE.md`.

Legacy code should be improved incrementally when practical.

---

## 2.3 Preserve Correct Existing Behavior

If a feature is already considered correct from the product perspective, architectural refactoring should preserve its behavior unless a behavioral change is explicitly intended.

---

## 2.4 Small, Traceable Changes

Prefer changes that can be understood and verified independently.

Avoid combining unrelated changes into one implementation task.

Bad:

```text id="q4kxaw"
Refactor booking
+
redesign dashboard
+
change payment
+
rename database
+
add new feature
```

Preferred:

```text id="w0m1qf"
Task A
→ Booking extraction

Task B
→ Payment boundary

Task C
→ Dashboard component refactor
```

---

## 2.5 Evidence Before Assumption

Before deciding how something works, inspect:

* requirements;
* current implementation;
* relevant models;
* routes;
* tests;
* related documentation.

Do not infer behavior merely from filenames.

---

# 3. Documentation Hierarchy

Before implementing a task, the contributor or agent should understand the following hierarchy:

```text id="j2ubm1"
PRODUCT.md
    ↓
What BookQu is
    ↓
REQUIREMENTS.md
    ↓
What BookQu must do
    ↓
ARCHITECTURE.md
    ↓
How BookQu should be built
    ↓
DEVELOPMENT.md
    ↓
How work should be performed
    ↓
TRACKER.md
    ↓
What is currently being worked on
```

When these documents conflict with historical documentation:

> Current documentation takes precedence.

Historical documents are reference material only.

---

# 4. Task Classification

Before starting a task, classify it.

Every task should belong to one of these categories.

## 4.1 Feature

Adds or changes product capability.

Example:

```text id="x8c1zv"
Add owner walk-in booking
```

---

## 4.2 Bug Fix

Corrects behavior that violates an existing requirement.

Example:

```text id="zqy05o"
Customer can book an already occupied schedule.
```

---

## 4.3 Refactor

Changes implementation structure without intentionally changing product behavior.

Example:

```text id="84dpit"
Extract booking logic from BookingController.
```

---

## 4.4 Technical Improvement

Improves infrastructure, maintainability, security, performance, testing, or developer experience.

Example:

```text id="lf64f0"
Add missing tenant isolation test.
```

---

## 4.5 Documentation

Changes product, requirement, architecture, or development documentation.

---

## 4.6 Product Change

Changes what BookQu is expected to do.

Examples:

```text id="wz6xv5"
Add customer account system.
Add Google Calendar synchronization.
Allow customers to select staff.
```

A product change must not begin as an ordinary coding task.

It must first go through requirement change control.

---

# 5. Before Starting Any Task

Every contributor should perform the following sequence.

```text id="fw9c0y"
1. Understand the task
2. Identify the product area
3. Identify the requirement
4. Inspect current implementation
5. Inspect related tests
6. Inspect architectural constraints
7. Determine affected modules
8. Define implementation scope
9. Implement
10. Test
11. Review
12. Update tracker/documentation
```

---

# 6. Step 1 — Understand the Task

The task must be understood in terms of behavior, not only implementation.

Ask:

```text id="up9n9x"
Who performs the action?
What does the user want to accomplish?
Under what conditions?
What should happen?
What should not happen?
What data is affected?
What other modules are affected?
```

A task such as:

```text id="f1fqa4"
"Fix booking page"
```

is too vague.

The task should become something like:

```text id="ylxqz8"
Customer cannot select a schedule that is already occupied.
The booking page should only allow eligible schedules.
```

---

# 7. Step 2 — Identify the Requirement

Every feature or behavior change must be mapped to a requirement ID.

Example:

```text id="h8x7ci"
FR-BOOKING-003
Customer can select an available schedule.
```

For a bug:

```text id="7lpg2l"
Requirement:
FR-BOOKING-017
Booking must not be created against an unavailable schedule.

Bug:
Current implementation allows an unavailable schedule in one flow.
```

For a refactor:

```text id="y9d8ld"
Behavior:
FR-BOOKING-005

Task:
Refactor booking creation without changing behavior.
```

---

# 8. Step 3 — Inspect Existing Implementation

Before creating new files, inspect the existing code.

At minimum, inspect:

```text id="x7pr1y"
Routes
Controller
Request validation
Models
Relevant services/actions
Middleware
Views
Tests
Related database migrations
```

The goal is to answer:

> “Does BookQu already solve part of this problem?”

Never assume a capability does not exist simply because its name is different.

---

# 9. Step 4 — Search Before Creating

Before creating a new:

* controller;
* service;
* action;
* helper;
* component;
* query;
* model method;

search the repository for an existing implementation.

Prefer reuse when the existing abstraction is appropriate.

Do not duplicate functionality merely because the existing implementation is located in legacy code.

If the existing implementation is structurally poor, consider refactoring it instead of creating a second implementation.

---

# 10. Step 5 — Identify the Domain

Every implementation task should have an identifiable domain.

Examples:

```text id="n9ezwl"
Authentication
Tenant
Service
Schedule
Booking
Customer
Payment
Subscription
Review
Voucher
Analytics
Notification
Asset
```

A task that touches several domains should explicitly identify the boundaries.

For example:

```text id="8gkz3x"
Booking creation
→ Booking domain
→ Schedule availability
→ Payment
→ Notification
```

This does not mean all logic belongs in one class.

---

# 11. Step 6 — Determine Whether the Task Changes Product Behavior

This is one of the most important development checks.

Ask:

> Does the user-visible behavior or business rule change?

If **No**:

```text id="pfv5xy"
Normal implementation / bug fix / refactor
```

If **Yes**:

```text id="khf9xj"
Requirement change required
```

Examples of product behavior changes:

```text id="a1ktw8"
Allow customers to create accounts.
Allow customers to choose staff.
Add a new payment method.
Change cancellation rules.
Change subscription limits.
Add multi-location businesses.
```

These must not be silently introduced.

---

# 12. Product Change Workflow

When a task changes product behavior:

```text id="mlwdd4"
Proposal
    ↓
Identify affected product concept
    ↓
Update PRODUCT.md if needed
    ↓
Update REQUIREMENTS.md
    ↓
Update ARCHITECTURE.md if technically relevant
    ↓
Update TRACKER.md
    ↓
Implement
    ↓
Test
```

If the change is significant enough to establish a long-term technical decision, create an ADR.

---

# 13. Requirement Conflict Handling

If implementation, tests, tracker, and documentation disagree:

Do not silently choose one.

Follow:

```text id="1hqn9a"
Identify conflict
    ↓
Determine intended current product behavior
    ↓
Document decision
    ↓
Update relevant documentation
    ↓
Implement
    ↓
Update tests
```

Example:

```text id="4r9v9c"
REQUIREMENTS.md:
Customer cannot select staff.

Current code:
Customer can select staff.

Action:
Do not automatically delete the feature.
Do not automatically preserve it.
Identify whether the behavior is accepted product behavior.
Then update the requirement and implementation accordingly.
```

---

# 14. Implementation Planning

Before modifying complex code, create a short implementation plan.

For a small change:

```text id="yfg2kv"
1. Add validation
2. Update booking action
3. Add test
4. Verify UI
```

For a complex change:

```text id="cuyl1u"
1. Identify existing booking path
2. Extract availability rule
3. Create application action
4. Update controller
5. Update tests
6. Verify customer flow
7. Verify owner flow
```

Do not create lengthy speculative plans for trivial changes.

---

# 15. Coding Rules

All new code must follow `docs/ARCHITECTURE.md`.

Important rules:

* keep controllers thin;
* keep business rules outside Blade;
* validate server-side;
* preserve tenant isolation;
* do not trust user-provided IDs;
* avoid duplicated business logic;
* keep external integrations isolated;
* create abstractions only when justified;
* use existing domain concepts;
* avoid introducing new terminology casually.

---

# 16. Controller Development

A controller should ideally perform:

```text id="j4d12a"
Receive Request
    ↓
Validate / Authorize
    ↓
Call Application Action
    ↓
Return Response
```

Avoid:

```text id="rjv31o"
Receive Request
    ↓
30 validation rules
    ↓
10 queries
    ↓
pricing calculation
    ↓
availability calculation
    ↓
payment API
    ↓
notifications
    ↓
cache invalidation
    ↓
response
```

The second structure is a refactoring candidate.

---

# 17. Business Logic Development

Business rules should have a clear owner.

Examples:

```text id="g6f8o1"
Availability
→ Schedule / Booking domain

Booking state transitions
→ Booking domain

Voucher eligibility
→ Voucher domain

Subscription feature entitlement
→ Subscription domain

Payment verification
→ Payment integration/application layer
```

Do not implement the same rule independently in:

```text id="s2lynu"
Controller A
Controller B
Blade
JavaScript
```

---

# 18. Validation Development

Validation should happen at multiple appropriate levels.

```text id="qw3w75"
UI validation
     ↓
Form Request validation
     ↓
Business rule validation
     ↓
Database constraints
```

Client-side validation improves user experience.

Server-side validation protects system correctness.

Database constraints protect data integrity.

---

# 19. Database Change Workflow

When a task requires schema changes:

```text id="m40s4e"
Requirement
    ↓
Data model impact
    ↓
Migration
    ↓
Model / relationship updates
    ↓
Seeder/test fixture updates
    ↓
Application changes
    ↓
Tests
```

Never change production schema by editing an old migration that has already been applied.

Create a new migration.

---

# 20. Database Safety

Before changing a database field or relationship, identify:

```text id="n8kvup"
Who reads it?
Who writes it?
Which models use it?
Which queries depend on it?
Which tests depend on it?
Which views depend on it?
Which seeders use it?
```

Database refactoring can have a much larger impact than the source file being changed.

---

# 21. Tenant Development Rules

Every tenant-scoped feature must be checked against:

```text id="eg9z5p"
Tenant resolution
Tenant authorization
Tenant query scope
Cross-tenant access
Cross-tenant mutation
```

Before considering a tenant-scoped feature complete, test at least:

```text id="58qv2p"
Tenant A can access Tenant A data.
Tenant A cannot access Tenant B data.
```

---

# 22. Booking Development Rules

Booking changes must be checked against:

```text id="0v0wcj"
Availability
Concurrency
Tenant ownership
Service ownership
Schedule ownership
Booking state
Payment state
Cancellation
Rescheduling
Multi-slot behavior
```

A booking feature is incomplete if it works only on the happy path.

---

# 23. Payment Development Rules

Any payment change must verify:

```text id="7z73vq"
Client request
Payment provider response
Webhook
Payment state
Booking state
Duplicate callback
Failed payment
Expired payment
Cancelled payment
```

Do not trust browser redirects or client-side payment status as the final financial authority.

---

# 24. Subscription Development Rules

Subscription changes must verify:

```text id="1d4vqf"
Plan
Subscription
Status
Entitlement
Trial
Expiration
Upgrade
Downgrade
Usage limit
```

Feature access logic must not be implemented independently in multiple controllers.

---

# 25. Frontend Development Rules

Frontend implementation should follow:

```text id="2sz20m"
Server
    ↓
Authoritative business state

Alpine / JavaScript
    ↓
Interaction and presentation
```

Frontend code may improve:

* interaction;
* responsiveness;
* previews;
* filtering;
* modal behavior.

Frontend code must not become the only place where business rules are enforced.

---

# 26. Blade Development Rules

When modifying a Blade page:

First inspect whether:

```text id="3snnw4"
a reusable component already exists
```

Then determine whether a repeated UI element should become a component.

Prefer:

```text id="f2x8tt"
Page
→ Component
→ Component
→ Component
```

over:

```text id="b5n4gc"
Page
→ thousands of repeated markup lines
```

---

# 27. Refactoring Workflow

Refactoring is not the same as feature development.

The preferred workflow is:

```text id="g7exeq"
1. Identify current behavior
2. Identify requirement
3. Identify tests
4. Characterize missing behavior if necessary
5. Define target structure
6. Refactor one boundary
7. Run tests
8. Continue
```

Do not rewrite the whole module before verifying intermediate states.

---

# 28. Characterization Tests

When a legacy module has insufficient tests, create tests that document its current important behavior before performing a risky refactor.

These tests answer:

> “What does this code actually do today?”

They do not necessarily define ideal future behavior.

Once the behavior is understood, the requirement can be compared against it.

---

# 29. Refactoring vs Product Change

A refactor should preserve:

```text id="d5mt2f"
inputs
outputs
business rules
authorization
data integrity
user-visible behavior
```

unless a behavior change is explicitly documented.

If behavior must change, treat it as:

```text id="b6ucm5"
Product / Requirement Change
```

not merely as:

```text id="k8g58o"
Refactor
```

---

# 30. Testing Workflow

Testing follows the implementation.

The minimum expectation is:

```text id="h9g86v"
Code change
    ↓
Relevant test
    ↓
Run test
    ↓
Inspect failure
    ↓
Fix
    ↓
Run relevant suite
```

Do not wait until the end of a large task to discover whether the implementation works.

---

# 31. Test Scope

Choose the smallest useful test scope first.

Example:

```text id="f5j9ck"
1. Specific test
2. Relevant module tests
3. Relevant integration tests
4. Full test suite
```

This speeds up development while still allowing final verification.

---

# 32. Test Expectations

A change should include or update tests when it changes:

* business rules;
* authorization;
* tenant isolation;
* booking state;
* payment behavior;
* subscription behavior;
* data integrity;
* customer management;
* important UI behavior.

Purely visual changes may require lighter testing where appropriate.

---

# 33. Failed Test Handling

When a test fails:

Do not immediately modify the test to make it pass.

Determine first:

```text id="xmyp3r"
Is the implementation wrong?
Is the requirement wrong?
Is the test outdated?
Is the test asserting legacy behavior?
Is there a data/setup issue?
```

Then make the appropriate change.

Tests are evidence, not automatically unquestionable truth.

---

# 34. Manual Verification

Manual verification is appropriate for:

* visual UI changes;
* responsive behavior;
* browser interaction;
* payment sandbox flow;
* external service behavior.

Document important manual verification results when automated verification is impractical.

---

# 35. Definition of Done

A task is not `Done` merely because the code compiles.

A feature or change should satisfy:

```text id="6kh0ae"
[ ] Requirement identified
[ ] Product scope confirmed
[ ] Architecture approach confirmed
[ ] Implementation completed
[ ] Validation completed
[ ] Authorization verified
[ ] Tenant isolation verified where applicable
[ ] Relevant tests added/updated
[ ] Tests pass
[ ] UI verified where applicable
[ ] No unintended scope expansion
[ ] Documentation updated where required
[ ] TRACKER.md updated
```

---

# 36. Definition of Done for Refactoring

A refactor is complete when:

```text id="9q3m4h"
[ ] Existing behavior is understood
[ ] Requirement remains satisfied
[ ] Target architecture is followed
[ ] Responsibilities are clearer
[ ] Duplication is reduced where relevant
[ ] Tests still pass
[ ] No security regression
[ ] No tenant-isolation regression
[ ] Documentation updated when architecture changes
```

---

# 37. Definition of Done for Bug Fixes

A bug fix is complete when:

```text id="30k4n0"
[ ] Root cause identified
[ ] Existing requirement identified
[ ] Failure reproduced where practical
[ ] Fix implemented
[ ] Regression test added or updated
[ ] Relevant test suite passes
[ ] No unrelated behavior changed
[ ] Tracker updated
```

---

# 38. Definition of Done for Product Changes

A product change is complete when:

```text id="z6vgp8"
[ ] Product change accepted
[ ] PRODUCT.md updated if necessary
[ ] REQUIREMENTS.md updated
[ ] ARCHITECTURE.md updated if necessary
[ ] TRACKER.md updated
[ ] Implementation completed
[ ] Tests updated
[ ] UI/UX verified
[ ] Documentation synchronized
```

---

# 39. Git Branch Strategy

Use branches to isolate work.

Recommended naming:

```text id="cb8n69"
feature/<short-description>
fix/<short-description>
refactor/<short-description>
docs/<short-description>
test/<short-description>
```

Examples:

```text id="yopq1c"
feature/customer-booking-management
fix/double-booking-validation
refactor/booking-action-layer
docs/update-requirements
test/tenant-isolation
```

Avoid vague names such as:

```text id="rsznjh"
update
new
fix2
testing
final
```

---

# 40. Commit Guidelines

Commits should represent a coherent change.

Preferred style:

```text id="8uomct"
feat: add walk-in booking validation
fix: prevent cross-tenant schedule access
refactor: extract booking creation action
test: cover duplicate payment callback
docs: update booking requirements
```

Avoid giant commits containing unrelated work.

---

# 41. Pull Request / Merge Review

Before merging a task, review:

```text id="kz1z94"
Product correctness
Requirement alignment
Architecture alignment
Security
Tenant isolation
Testing
Scope
Code readability
```

The review should ask:

> “Does this implementation solve the requested problem without silently changing something else?”

---

# 42. Scope Control

Do not expand the task merely because another improvement is noticed.

Example:

Task:

```text id="xjb5i5"
Fix booking reschedule validation.
```

During implementation, the contributor notices:

```text id="p0fwe2"
Customer dashboard is visually outdated.
```

Do not automatically redesign the dashboard.

Record the issue separately.

Scope discipline is required to prevent uncontrolled project expansion.

---

# 43. Opportunistic Refactoring Rule

Small related refactoring is allowed when it clearly reduces risk.

Example:

```text id="gzt5vw"
Task:
Fix booking cancellation.

During the fix:
Extract duplicated cancellation validation into one action.
```

This is acceptable because the refactor directly supports the task.

Avoid unrelated large-scale refactors during the same change.

---

# 44. Technical Debt Rule

When technical debt is discovered:

Classify it as:

```text id="c3vhv7"
Critical
High
Medium
Low
```

Examples:

```text id="n3u2d4"
Critical:
Tenant data exposure.

High:
Payment state can become inconsistent.

Medium:
Large controller.

Low:
Inconsistent naming in legacy UI.
```

Critical and high-impact technical debt should not be postponed merely because a feature is otherwise functional.

---

# 45. Legacy Code Rule

Legacy code may remain temporarily.

Do not:

```text id="g7umfr"
copy legacy pattern
```

unless necessary.

Do:

```text id="xln2wo"
understand legacy behavior
→ preserve it when required
→ move new code toward target architecture
```

---

# 46. New Code Rule

New code must not introduce known legacy anti-patterns.

For example, if the target architecture says:

```text id="3x0qj5"
Booking business logic → Action / Domain layer
```

do not add another 200-line booking workflow to a controller simply because the old controller already has 1,500 lines.

---

# 47. Duplicate Logic Rule

Before implementing a rule, search for an existing implementation.

If duplicate logic is found:

```text id="6nnq2v"
Reuse
or
Extract
or
Document why duplication is required
```

Do not silently create a third copy.

---

# 48. Naming Change Rule

A naming inconsistency does not automatically justify a repository-wide rename.

When a legacy term such as:

```text id="xv4y6v"
Program
```

is being migrated to:

```text id="wm7u2v"
Service
```

prefer incremental migration.

A large rename should be a dedicated refactoring task.

---

# 49. AI-Assisted Development

AI agents are allowed to:

* inspect the repository;
* analyze dependencies;
* propose implementation plans;
* write code;
* write tests;
* refactor code;
* update documentation.

However, AI agents must follow the same requirement and architecture boundaries as human developers.

AI-generated code is not exempt from review.

---

# 50. AI Agent Required Reading

Before a complex implementation task, the agent should read:

```text id="ulj9m6"
AGENTS.md
docs/PRODUCT.md
docs/REQUIREMENTS.md
docs/ARCHITECTURE.md
docs/DEVELOPMENT.md
docs/TRACKER.md
```

For small tasks, the agent may read only the relevant sections when context is already established.

---

# 51. AI Agent Task Workflow

The standard agent workflow is:

```text id="5x0v74"
Receive Task
    ↓
Identify Requirement
    ↓
Identify Domain
    ↓
Inspect Existing Implementation
    ↓
Inspect Related Tests
    ↓
Check Architecture
    ↓
Create Implementation Plan
    ↓
Implement
    ↓
Run Tests
    ↓
Review Result
    ↓
Update Documentation / Tracker
```

---

# 52. AI Agent Must Not

AI agents must not:

* invent requirements;
* silently change product scope;
* assume legacy code is the desired architecture;
* create duplicate implementations unnecessarily;
* bypass authorization;
* bypass tenant isolation;
* trust client-side business state;
* remove tests simply because they fail;
* change tests solely to make incorrect code pass;
* add unrelated features;
* rewrite entire modules without justification;
* introduce abstractions without a clear purpose;
* silently rename core domain concepts.

---

# 53. AI Agent Conflict Rule

If an agent finds:

```text id="dz2j5y"
Requirement ≠ Code
```

or:

```text id="1z93f1"
Requirement ≠ Test
```

or:

```text id="t7s1w0"
Architecture ≠ Existing implementation
```

the agent must not silently choose one.

The agent should determine whether the task is:

```text id="t1g8iv"
Bug
Refactor
Requirement Change
Documentation Drift
Legacy Behavior
```

and handle it accordingly.

---

# 54. AI Agent Minimal Change Principle

When implementing a task, the agent should make the smallest change that correctly satisfies:

```text id="lj6nha"
Requirement
+
Security
+
Architecture
+
Tests
```

Avoid broad changes unless they are necessary.

---

# 55. AI Agent Investigation Principle

Before creating new architecture, the agent should inspect:

```text id="4ndc73"
existing classes
existing methods
existing routes
existing tests
existing components
existing migrations
existing services
```

The objective is to extend the system coherently rather than creating parallel solutions.

---

# 56. Agent Output Expectations

For non-trivial tasks, the agent's implementation result should make clear:

```text id="z7cevk"
What changed
Which requirement was affected
Which files changed
Which tests were added/updated
What was verified
Any remaining limitation
```

This improves human review and future maintenance.

---

# 57. Development Checklist

Before starting:

```text id="zqt6of"
[ ] Understand task
[ ] Identify requirement
[ ] Identify domain
[ ] Inspect implementation
[ ] Inspect tests
[ ] Check architecture
[ ] Determine scope
```

During development:

```text id="1y8n5h"
[ ] Follow target architecture
[ ] Preserve tenant isolation
[ ] Preserve authorization
[ ] Avoid duplicate logic
[ ] Keep scope controlled
[ ] Add/update tests
```

Before completion:

```text id="2at3b0"
[ ] Tests pass
[ ] UI verified where applicable
[ ] No unintended behavior changed
[ ] Documentation updated if required
[ ] Tracker updated
```

---

# 58. Emergency / Hotfix Rule

For urgent production fixes, the contributor may temporarily prioritize restoration of service.

However:

```text id="f6fp0r"
Hotfix
  ↓
Restore stability
  ↓
Add regression test
  ↓
Document root cause
  ↓
Refactor if needed
  ↓
Synchronize requirements/architecture if behavior changed
```

A hotfix must not become a permanent excuse to bypass architecture.

---

# 59. Documentation Synchronization Rule

Whenever development changes one of the following:

```text id="p4gpg3"
Product behavior
Requirement
Business rule
Architecture
Public domain terminology
```

the corresponding documentation must be updated.

Source code and documentation must not be allowed to drift intentionally.

---

# 60. Tracker Synchronization Rule

After a meaningful task:

```text id="w57gxt"
TRACKER.md
```

must reflect the latest status.

At minimum, update:

* status;
* owner;
* implementation state;
* test state;
* important notes;
* refactor state where applicable.

---

# 61. Development State Model

BookQu tasks should use:

```text id="j3x2y6"
Planned
   ↓
In Progress
   ↓
Testing
   ↓
Done
```

Alternative states:

```text id="o0cdbf"
Blocked
Needs Refactor
Deprecated
```

---

# 62. Feature Completion vs Architecture Completion

These are separate states.

A feature may be:

```text id="r0i6x6"
Feature:
Done

Architecture:
Needs Refactor
```

This means:

> The product behavior currently works, but the implementation should later be improved.

This distinction is intentionally important for BookQu because the current codebase contains functionality that was developed before the target architecture was defined.

---

# 63. Product Freeze During Refactoring

During major architectural refactoring, avoid introducing unrelated product changes unless required.

The preferred sequence is:

```text id="7i8r5q"
Stabilize Behavior
      ↓
Refactor
      ↓
Verify
      ↓
Resume New Features
```

This reduces the number of moving parts during architectural work.

---

# 64. Major Refactor Procedure

A major refactor should follow:

```text id="ggxgl6"
1. Define scope
2. Define target boundary
3. Identify behavior
4. Confirm tests
5. Add characterization tests if needed
6. Refactor incrementally
7. Run tests after each major step
8. Review dependencies
9. Review tenant isolation
10. Update architecture documentation
11. Update tracker
```

---

# 65. Production Safety Principle

Development decisions should prioritize:

```text id="o7jtdq"
Data Integrity
Security
Booking Correctness
Payment Correctness
Tenant Isolation
```

before:

```text id="wmj8cn"
Code elegance
Minor performance optimization
Developer convenience
```

---

# 66. Golden Rule

The most important development rule for BookQu is:

> **Do not solve a problem by creating another undocumented problem.**

Every meaningful change should preserve the relationship:

```text id="2vy4pe"
Product
   ↓
Requirement
   ↓
Architecture
   ↓
Implementation
   ↓
Test
   ↓
Tracker
```

If one link changes, evaluate the others.

---

# 67. Final Development Workflow

The complete BookQu development lifecycle is:

```text id="f1yz80"
                    ┌──────────────────────┐
                    │      TASK / BUG      │
                    └──────────┬───────────┘
                               ↓
                    ┌──────────────────────┐
                    │ Identify Requirement │
                    └──────────┬───────────┘
                               ↓
                    ┌──────────────────────┐
                    │  Identify the Domain │
                    └──────────┬───────────┘
                               ↓
                    ┌──────────────────────┐
                    │ Inspect Existing Code│
                    └──────────┬───────────┘
                               ↓
                    ┌──────────────────────┐
                    │ Inspect Existing Test│
                    └──────────┬───────────┘
                               ↓
                    ┌──────────────────────┐
                    │ Check Architecture  │
                    └──────────┬───────────┘
                               ↓
                    ┌──────────────────────┐
                    │ Plan Implementation │
                    └──────────┬───────────┘
                               ↓
                    ┌──────────────────────┐
                    │      Implement       │
                    └──────────┬───────────┘
                               ↓
                    ┌──────────────────────┐
                    │    Run Tests         │
                    └──────────┬───────────┘
                               ↓
                    ┌──────────────────────┐
                    │ Review & Verification│
                    └──────────┬───────────┘
                               ↓
                    ┌──────────────────────┐
                    │ Update Documentation │
                    │     & Tracker        │
                    └──────────┬───────────┘
                               ↓
                         COMPLETE
```

---

# 68. Final Principle

BookQu development should remain:

```text id="w0v1bd"
Requirement-driven
Domain-aware
Architecture-conscious
Tested
Traceable
Incremental
```

The goal is not to eliminate every imperfection from the codebase immediately.

The goal is to ensure that from this point forward:

> **Every change has a reason, every reason has a requirement, every implementation has an architectural home, and every important behavior has verification.**

---

# 69. Production Scheduler Configuration

Production servers must run the Laravel Task Scheduler to ensure automated payment expiration and subscription lifecycle tasks execute on time.

### 69.1 System Cron Entry

Add the following cron entry to the server (e.g. via `crontab -e` for user `www-data` or deployment user):

```cron
* * * * * cd /path/to/bookqu && php artisan schedule:run >> /dev/null 2>&1
```

### 69.2 Registered Scheduled Tasks

| Command | Frequency | Purpose |
| :--- | :--- | :--- |
| `php artisan bookings:expire-payments` | Every 15 minutes (`*/15 * * * *`) | Automatically cancels pending bookings whose payment deadline has expired, updates payment status to `gagal`, releases schedule slots, and invalidates availability cache. |
| `php artisan app:check-expired-subscriptions` | Daily at 00:00 (`0 0 * * *`) | Evaluates tenant subscription expiration dates and transitions lapsed subscriptions to expired. |

### 69.3 Verification & Manual Execution

To verify scheduled tasks:

```bash
php artisan schedule:list
```

To run dry-run simulation of payment expiry without mutating data:

```bash
php artisan bookings:expire-payments --dry-run
```

To execute manually:

```bash
php artisan bookings:expire-payments
```
