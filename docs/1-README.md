# BookQu Documentation Map

> **Document Status:** Active
> **Purpose:** Documentation map and source-of-truth guide
> **Version:** 1.0
> **Last Updated:** 2026-09-30
>
> This document defines the structure, responsibility, authority, and recommended reading flow of the BookQu documentation system.
>
> It does not define product behavior, detailed system implementation, operational procedures, or architectural decisions itself.

---

# 1. Purpose

The BookQu documentation system exists to provide a clear and maintainable source of truth for development and future system evolution.

The documentation is intentionally separated by responsibility so that each document answers a specific question.

The documentation system should allow a developer or AI agent to determine:

```text
What is BookQu?
        ↓
What must BookQu do?
        ↓
How should BookQu be architected?
        ↓
How does the current system actually work?
        ↓
How should changes be performed?
        ↓
How is the system operated?
        ↓
What is currently implemented?
        ↓
Why were important architectural decisions made?
```

Each question has a primary documentation authority.

---

# 2. Documentation Structure

The active documentation structure is:

```text
bookqu/
│
├── AGENT.md
│   └── AI agent behavior and development rules
│
└── docs/
    ├── 1-README.md
    │   └── Documentation map and source-of-truth guide
    │
    ├── 2-PRODUCT.md
    │   └── Product definition
    │
    ├── 3-REQUIREMENT.md
    │   └── System and behavioral requirements
    │
    ├── 4-ARCHITECTURE.md
    │   └── Architectural principles and target structure
    │
    ├── 5-DEVELOPMENT.md
    │   └── Development and change workflow
    │
    ├── 6-TRACKER.md
    │   └── Current implementation and project status
    │
    ├── 7-SYSTEM-DESIGN.md
    │   └── Current system design and implementation model
    │
    ├── 8-OPERATIONS.md
    │   └── Runtime, operational, and verification guidance
    │
    └── adr/
        ├── README.md
        └── ADR-*.md
```

The structure is intentionally small.

New documentation should only be introduced when the information cannot be maintained clearly within an existing document.

---

# 3. Documentation Authority Model

BookQu uses a responsibility-based authority model.

There is no single document that is authoritative for every aspect of the system.

Each document is authoritative within its own scope.

| Document                  | Authority               | Primary Question                                  |
| ------------------------- | ----------------------- | ------------------------------------------------- |
| `AGENT.md`                | AI agent behavior       | How must an AI agent work?                        |
| `docs/1-README.md`        | Documentation map       | Where should information be found?                |
| `docs/2-PRODUCT.md`       | Product definition      | What is BookQu?                                   |
| `docs/3-REQUIREMENT.md`   | Required behavior       | What must BookQu do?                              |
| `docs/4-ARCHITECTURE.md`  | Architectural direction | How should BookQu be structured?                  |
| `docs/5-DEVELOPMENT.md`   | Development workflow    | How should changes be performed?                  |
| `docs/6-TRACKER.md`       | Current project status  | What is currently implemented or incomplete?      |
| `docs/7-SYSTEM-DESIGN.md` | Current system design   | How does the current system actually work?        |
| `docs/8-OPERATIONS.md`    | Operational guidance    | How is the system operated and verified?          |
| `docs/adr/`               | Architectural rationale | Why was an important architectural decision made? |

This separation prevents one document from becoming a conflicting mixture of product, architecture, implementation, and operational information.

---

# 4. AGENT.md

Location:

```text
/AGENT.md
```

Primary responsibility:

> Define how AI agents must operate when working on BookQu.

`AGENT.md` contains rules concerning:

* repository inspection;
* documentation reading;
* requirement identification;
* scope control;
* tenant isolation;
* security;
* booking safety;
* payment safety;
* implementation discipline;
* testing;
* documentation synchronization;
* conflict handling;
* change safety.

`AGENT.md` does not replace the other documentation.

It defines agent behavior rather than becoming the complete source of truth for BookQu.

When an agent needs information about the product or system, it should consult the appropriate documentation defined in this document.

---

# 5. 2-PRODUCT.md

Location:

```text
/docs/2-PRODUCT.md
```

Primary responsibility:

> Define what BookQu is as a product.

It contains product-level information such as:

* product identity;
* product purpose;
* target users;
* product concepts;
* canonical terminology;
* core product loop;
* product capabilities;
* product boundaries;
* product scope.

`2-PRODUCT.md` should answer:

> What is BookQu and what belongs to the product?

It should not become a detailed technical implementation document.

It should not depend on specific:

```text
classes
controllers
database migrations
cache keys
service wiring
internal helper methods
deployment details
```

unless those details are directly relevant to the product definition.

---

# 6. 3-REQUIREMENT.md

Location:

```text
/docs/3-REQUIREMENT.md
```

Primary responsibility:

> Define what the BookQu system must do.

It contains:

* functional requirements;
* business rules;
* non-functional requirements;
* security requirements;
* authorization requirements;
* acceptance criteria;
* requirement identifiers;
* requirement status;
* traceability.

`3-REQUIREMENT.md` should answer:

> What behavior is required from BookQu?

Requirements should describe behavior and business expectations rather than implementation structure.

For example, a requirement should describe:

```text
A customer must not be able to create a booking for an occupied slot.
```

rather than making the requirement depend on a specific implementation such as:

```text
BookingRules.php
CreateBooking.php
SomeController.php
```

Implementation can change while the requirement remains valid.

---

# 7. 4-ARCHITECTURE.md

Location:

```text
/docs/4-ARCHITECTURE.md
```

Primary responsibility:

> Define how BookQu should be structured.

This document contains:

* architectural principles;
* application boundaries;
* domain boundaries;
* layer responsibilities;
* tenant architecture;
* booking architecture;
* payment architecture;
* schedule architecture;
* infrastructure boundaries;
* testing architecture;
* future-ready structural principles.

`4-ARCHITECTURE.md` should answer:

> How should BookQu be built and evolved?

The Architecture document should primarily describe stable architectural rules and target structure.

It should not become a historical record of previous refactoring work.

The completed RF-00 through RF-09 refactor is already part of the current implementation. Its execution history is preserved by Git history rather than by active refactor documentation.

Architecture statements should therefore focus on durable responsibilities and boundaries rather than describing the historical sequence of implementation changes.

For example:

```text
Business operations should be handled through an appropriate
application boundary rather than becoming concentrated in HTTP
controllers.
```

is a durable architectural rule.

---

# 8. 5-DEVELOPMENT.md

Location:

```text
/docs/5-DEVELOPMENT.md
```

Primary responsibility:

> Define how development changes are performed.

It governs topics such as:

* task classification;
* requirement-first development;
* repository inspection;
* implementation planning;
* coding rules;
* testing;
* debugging;
* refactoring;
* database changes;
* security considerations;
* tenant safety;
* booking safety;
* payment safety;
* documentation updates;
* definition of done;
* AI-assisted development workflow.

`5-DEVELOPMENT.md` should answer:

> How should a developer or AI agent safely make changes to BookQu?

It should not become:

```text
Product Specification
System Design
Operational Runbook
Historical Refactor Log
```

Those responsibilities belong elsewhere.

---

# 9. 6-TRACKER.md

Location:

```text
/docs/6-TRACKER.md
```

Primary responsibility:

> Track the current implementation and project status.

It may contain:

* implementation status;
* requirement status;
* verification status;
* architecture status;
* technical debt;
* blockers;
* remaining work;
* testing status;
* current priorities.

`6-TRACKER.md` should answer:

> What is currently implemented, incomplete, under verification, or requiring future work?

The tracker does not define product behavior.

The tracker does not define the architectural rules.

It records the current status of implementation against the accepted product and technical baseline.

Historical completion of RF-00 through RF-09 may be referenced when useful for current status, but the tracker should not become a reconstruction of the refactor execution history.

---

# 10. 7-SYSTEM-DESIGN.md

Location:

```text
/docs/7-SYSTEM-DESIGN.md
```

Primary responsibility:

> Describe how the current BookQu system actually works.

This document is the bridge between architectural principles and implementation.

It should contain current system knowledge such as:

* current system model;
* current component responsibilities;
* major system flows;
* domain interactions;
* state transitions;
* system invariants;
* security boundaries;
* data flow;
* current implementation mapping;
* integration boundaries;
* important dependencies;
* current extension points.

`7-SYSTEM-DESIGN.md` should answer:

> How does the current system actually operate internally?

The distinction between Architecture and System Design is:

```text
4-ARCHITECTURE.md
→ How BookQu should be structured.

7-SYSTEM-DESIGN.md
→ How BookQu currently works.
```

The System Design document should be current-oriented.

It should not become a history of RF-00 through RF-09.

It should also avoid unnecessary coupling to implementation details that are likely to change.

Prefer:

```text
Responsibility
+
Current implementation
+
Invariant
+
Dependency
+
Extension point
```

over a simple inventory of every class and file.

---

# 11. 8-OPERATIONS.md

Location:

```text
/docs/8-OPERATIONS.md
```

Primary responsibility:

> Describe how the BookQu system is run, monitored, verified, and operated.

It should contain operational knowledge such as:

* runtime prerequisites;
* environment requirements;
* database requirements;
* cache requirements;
* queue/background processing requirements;
* scheduler requirements;
* payment integration requirements;
* storage requirements;
* production verification;
* health checks;
* smoke checks;
* operational troubleshooting;
* backup and recovery expectations.

`8-OPERATIONS.md` should answer:

> How do we run and verify BookQu safely?

Operational procedures should not be scattered across architecture or development documentation.

Deployment-specific configuration remains governed by the actual deployment configuration in the repository.

The Operations document explains the operational requirements and procedures; it does not replace deployment configuration.

---

# 12. adr/

Location:

```text
/docs/adr/
```

Primary responsibility:

> Record the rationale behind important architectural decisions.

The ADR system should contain:

```text
docs/adr/
├── README.md
└── ADR-*.md
```

An ADR should explain:

```text
Context
Decision
Rationale
Consequences
Status
```

ADR should be used for significant, long-lived architectural decisions.

Examples may include:

* multi-tenancy strategy;
* booking concurrency model;
* payment state separation;
* customer management token scope;
* external integration boundaries;
* important architectural boundaries.

ADR should not become a collection of minor implementation notes.

The Architecture document defines the architectural rule.

The ADR explains why that rule was adopted.

---

# 13. Current, Target, and Historical Information

BookQu separates three kinds of information.

## 13.1 Current

Current information describes how the product and system exist now.

Examples:

```text
Current product behavior
Current requirements
Current system behavior
Current implementation model
Current operational requirements
Current project status
Current system invariants
```

Primary authorities include:

```text
2-PRODUCT.md
3-REQUIREMENT.md
6-TRACKER.md
7-SYSTEM-DESIGN.md
8-OPERATIONS.md
```

---

## 13.2 Target

Target information describes how the system should be structured and evolved.

Examples:

```text
Architectural principles
Layer boundaries
Responsibility boundaries
Future-ready design principles
Extension rules
```

Primary authorities include:

```text
4-ARCHITECTURE.md
ADR
```

Target architecture must not be presented as already implemented unless the current implementation actually supports it.

---

## 13.3 Historical

Historical information describes how BookQu evolved.

Examples include:

```text
Pre-refactor architecture
Old implementation baselines
RF-00 through RF-09 execution
Previous architecture snapshots
Previous implementation decisions
```

Historical evolution is preserved through:

```text
Git commits
Pull requests
Repository history
Release history where applicable
```

Historical material must not override current documentation.

The active documentation system is intentionally focused on the current system and future development rather than preserving the execution history of completed refactors.

---

# 14. Documentation vs Source Code vs Tests

The documentation system, implementation, and tests have different roles.

```text
Documentation
→ Defines meaning, requirements, structure, and procedures.

Source Code
→ Implements the system.

Tests
→ Provide evidence of behavior and correctness.
```

The intended relationship is:

```text
PRODUCT
    ↓
REQUIREMENT
    ↓
ARCHITECTURE
    ↓
SYSTEM DESIGN
    ↓
IMPLEMENTATION
    ↓
TESTS
```

Development workflow governs how changes move through this model:

```text
TASK
    ↓
REQUIREMENT
    ↓
DESIGN
    ↓
IMPLEMENTATION
    ↓
TEST
    ↓
DOCUMENTATION
    ↓
TRACKER
```

Operations provides the runtime dimension:

```text
SYSTEM DESIGN
      ↓
RUNTIME
      ↓
OPERATIONS
      ↓
VERIFICATION
```

ADRs provide the decision rationale dimension:

```text
ARCHITECTURE
      ↓
ARCHITECTURAL DECISION
      ↓
ADR
```

---

# 15. Which Document Should I Read?

## What is BookQu?

Read:

```text
docs/2-PRODUCT.md
```

---

## Is this capability part of the product?

Read:

```text
docs/2-PRODUCT.md
docs/3-REQUIREMENT.md
```

---

## What must this feature do?

Read:

```text
docs/3-REQUIREMENT.md
```

---

## What business rules apply?

Read:

```text
docs/3-REQUIREMENT.md
```

---

## Where should this responsibility belong?

Read:

```text
docs/4-ARCHITECTURE.md
docs/7-SYSTEM-DESIGN.md
```

---

## How does this subsystem currently work?

Read:

```text
docs/7-SYSTEM-DESIGN.md
```

and then inspect the relevant implementation and tests.

---

## How should this change be implemented?

Read:

```text
AGENT.md
docs/2-PRODUCT.md
docs/3-REQUIREMENT.md
docs/4-ARCHITECTURE.md
docs/5-DEVELOPMENT.md
docs/7-SYSTEM-DESIGN.md
```

Only read the portions relevant to the task.

---

## How is the system run or verified operationally?

Read:

```text
docs/8-OPERATIONS.md
```

---

## Why does this architectural decision exist?

Read:

```text
docs/4-ARCHITECTURE.md
docs/adr/
```

---

## What is currently implemented or incomplete?

Read:

```text
docs/6-TRACKER.md
```

---

## How did BookQu evolve historically?

Use:

```text
Git history
```

Do not use historical material as the current system authority.

---

# 16. Recommended Reading by Task

## New Feature

```text
AGENT.md
    ↓
docs/2-PRODUCT.md
    ↓
docs/3-REQUIREMENT.md
    ↓
docs/4-ARCHITECTURE.md
    ↓
docs/7-SYSTEM-DESIGN.md
    ↓
docs/5-DEVELOPMENT.md
    ↓
Implementation
    ↓
Tests
    ↓
docs/6-TRACKER.md
```

---

## Bug Fix

```text
AGENT.md
    ↓
docs/3-REQUIREMENT.md
    ↓
docs/7-SYSTEM-DESIGN.md
    ↓
Current Implementation
    ↓
Relevant Tests
    ↓
docs/5-DEVELOPMENT.md
```

Consult `docs/4-ARCHITECTURE.md` when the bug involves responsibility boundaries or architectural behavior.

---

## Refactoring

```text
AGENT.md
    ↓
docs/3-REQUIREMENT.md
    ↓
docs/4-ARCHITECTURE.md
    ↓
docs/7-SYSTEM-DESIGN.md
    ↓
Relevant Tests
    ↓
docs/5-DEVELOPMENT.md
    ↓
docs/6-TRACKER.md
```

A refactor should preserve accepted product behavior unless the task explicitly changes that behavior.

---

## Product Change

```text
AGENT.md
    ↓
docs/2-PRODUCT.md
    ↓
docs/3-REQUIREMENT.md
    ↓
docs/4-ARCHITECTURE.md
    ↓
docs/7-SYSTEM-DESIGN.md
    ↓
docs/5-DEVELOPMENT.md
    ↓
Implementation
    ↓
Tests
    ↓
docs/6-TRACKER.md
```

---

## Architectural Decision

```text
docs/4-ARCHITECTURE.md
    ↓
docs/7-SYSTEM-DESIGN.md
    ↓
ADR
    ↓
docs/5-DEVELOPMENT.md
    ↓
Implementation
```

---

## Operational Change

```text
docs/8-OPERATIONS.md
    ↓
docs/7-SYSTEM-DESIGN.md
    ↓
Relevant Configuration
    ↓
Verification
    ↓
docs/6-TRACKER.md
```

---

# 17. Source-of-Truth Rules

When information conflicts, first identify what kind of information is in conflict.

## Product Meaning

Use:

```text
docs/2-PRODUCT.md
```

---

## Required Behavior

Use:

```text
docs/3-REQUIREMENT.md
```

---

## Architectural Direction

Use:

```text
docs/4-ARCHITECTURE.md
```

---

## Current System Behavior

Use:

```text
docs/7-SYSTEM-DESIGN.md
```

and verify against implementation and tests where necessary.

---

## Development Process

Use:

```text
docs/5-DEVELOPMENT.md
```

---

## Operational Procedure

Use:

```text
docs/8-OPERATIONS.md
```

---

## Current Project Status

Use:

```text
docs/6-TRACKER.md
```

---

## Architectural Rationale

Use:

```text
docs/adr/
```

---

## Historical Evolution

Use:

```text
Git history
```

Historical sources must not override current authoritative documentation.

---

# 18. Conflict Handling

When documentation, code, or tests disagree:

```text
Do not silently choose an interpretation.
```

First determine the conflict type:

```text
Product conflict
Requirement conflict
Architecture conflict
System-design conflict
Implementation drift
Test drift
Documentation drift
Operational drift
Legacy behavior
```

Then identify the correct authority.

A typical resolution flow is:

```text
Identify intended behavior
        ↓
Update the authoritative documentation
        ↓
Update implementation when required
        ↓
Update tests
        ↓
Update tracker
```

Documentation must not be rewritten merely to hide incorrect implementation.

Likewise, implementation must not be changed merely because an obsolete document contains a different statement.

---

# 19. Documentation Minimalism

Do not create a new documentation file simply because a new topic appears.

First determine whether it belongs in an existing document.

The current system already provides dedicated authorities for:

```text
Product
Requirements
Architecture
Development
Tracker
System Design
Operations
Architectural Decisions
```

A new documentation category should be introduced only when:

1. the topic has a distinct long-term responsibility;
2. the information cannot be maintained clearly in an existing document;
3. the new document improves source-of-truth clarity rather than creating another layer of duplication.

Do not split documents merely because they are long.

Do not duplicate the same system rule across multiple authorities.

---

# 20. Documentation Maintenance Rules

Documentation must evolve together with the system, but only the relevant authority should be changed.

Examples:

### Product change

Potentially affects:

```text
2-PRODUCT.md
3-REQUIREMENT.md
4-ARCHITECTURE.md
7-SYSTEM-DESIGN.md
6-TRACKER.md
```

only when the change affects those responsibilities.

---

### Architectural change

Potentially affects:

```text
4-ARCHITECTURE.md
7-SYSTEM-DESIGN.md
5-DEVELOPMENT.md
6-TRACKER.md
ADR
```

only where applicable.

---

### Operational change

Potentially affects:

```text
8-OPERATIONS.md
7-SYSTEM-DESIGN.md
5-DEVELOPMENT.md
6-TRACKER.md
```

only where applicable.

---

### Requirement clarification

Normally affects:

```text
3-REQUIREMENT.md
```

and related documentation or implementation when necessary.

Do not modify unrelated documents merely because a change occurred somewhere else in the repository.

---

# 21. Future-Proofing Principle

The documentation system is intended to support future BookQu development without repeatedly restructuring the documentation hierarchy.

The preferred approach is:

```text
Stable authority
+
Clear responsibility
+
Current system documentation
+
Explicit architectural direction
+
Decision records
```

Avoid documentation that depends unnecessarily on:

```text
temporary class names
temporary directory structures
one-time refactor phases
historical implementation details
short-lived development tasks
```

The current system may change.

The documentation structure should not need to change every time a class or implementation detail moves.

---

# 22. Historical Refactor Work

RF-00 through RF-09 represent completed architectural refactor work.

Those work orders are no longer part of the active documentation structure.

The current documentation describes the system after those changes rather than describing how the changes were executed.

Historical refactor information remains available through Git history.

This distinction is intentional:

```text
Active Documentation
→ Understand and evolve the current system.

Git History
→ Understand how the repository evolved.
```

AI agents should not need to read historical refactor documentation to understand current BookQu behavior or architecture.

Historical investigation should be performed only when the task specifically requires understanding repository evolution.

---

# 23. Final Documentation Model

The complete BookQu documentation model is:

```text
                         AGENT.md
                            │
                    Documentation Rules
                            │
                     1-README.md
                            │
              Documentation Map / Authority
                            │
        ┌───────────────────┼───────────────────┐
        ↓                   ↓                   ↓
     PRODUCT           REQUIREMENT         ARCHITECTURE
        │                   │                   │
     WHAT IT IS        WHAT IT MUST DO    HOW IT SHOULD
                                             BE BUILT
        │                   │                   │
        └───────────────────┼───────────────────┘
                            ↓
                     SYSTEM DESIGN
                            │
                    HOW IT CURRENTLY
                         WORKS
                            │
                            ↓
                      IMPLEMENTATION
                            │
                            ↓
                          TESTS
                            │
                            ↓
                        TRACKER
                            │
                  CURRENT PROJECT STATE


ARCHITECTURE
      │
      ↓
     ADR
      │
WHY THE DECISION EXISTS


SYSTEM DESIGN
      │
      ↓
 OPERATIONS
      │
HOW THE SYSTEM IS RUN
AND VERIFIED


DEVELOPMENT
      │
      ↓
HOW ALL CHANGES MOVE
THROUGH THE SYSTEM
```

---

# 24. Final Principle

The BookQu documentation system exists to maintain one clear and durable understanding of the project.

A contributor or AI agent should be able to determine:

```text
What BookQu is
        ↓
What BookQu must do
        ↓
How BookQu should be structured
        ↓
How the current system actually works
        ↓
How changes should be performed
        ↓
How the system is operated
        ↓
What is currently implemented
        ↓
Why important architectural decisions exist
```

Each question should have one clear primary source.

The documentation system should remain:

```text
Clear
+
Current
+
Traceable
+
Maintainable
+
Future-ready
```

The objective is not to preserve every detail of the project's history inside active documentation.

The objective is to provide a reliable source of truth for the current BookQu system and a stable foundation for its future development.
