# BookQu Documentation

> **Document Status:** Active
> **Purpose:** Documentation navigation and source-of-truth map
> **Last Updated:** 2026-09-26

---

# 1. Purpose

This directory contains the authoritative documentation used to understand, develop, maintain, and evolve BookQu.

The documentation is intentionally separated by responsibility.

Each document answers a different question.

Do not use one document as a substitute for another.

---

# 2. Documentation Structure

```text
bookqu/
│
├── AGENTS.md
│
└── docs/
    ├── README.md
    ├── PRODUCT.md
    ├── REQUIREMENTS.md
    ├── ARCHITECTURE.md
    ├── DEVELOPMENT.md
    ├── TRACKER.md
    │
    ├── adr/
    │   └── ...
    │
    └── archive/
        └── ...
```

---

# 3. Documentation Authority

The active documentation hierarchy is:

```text
AGENTS.md
    ↓
PRODUCT.md
    ↓
REQUIREMENTS.md
    ↓
ARCHITECTURE.md
    ↓
DEVELOPMENT.md
    ↓
TRACKER.md
```

Each document has a different responsibility.

---

# 4. AGENTS.md

Location:

```text
/AGENTS.md
```

Question answered:

> **How must an AI agent work on BookQu?**

Contains:

* AI agent rules;
* mandatory reading order;
* scope control;
* architecture rules;
* security rules;
* tenant isolation rules;
* coding boundaries;
* testing expectations;
* documentation synchronization;
* agent behavior when conflicts are discovered.

This is the first document an AI agent should read.

---

# 5. PRODUCT.md

Location:

```text
/docs/PRODUCT.md
```

Question answered:

> **What is BookQu?**

Defines:

* product identity;
* product goals;
* target users;
* domain concepts;
* canonical terminology;
* core business loop;
* product scope;
* product boundaries;
* core operations;
* supporting capabilities;
* platform capabilities;
* future capabilities.

This document defines the current conceptual identity of BookQu.

It does not define detailed implementation.

---

# 6. REQUIREMENTS.md

Location:

```text
/docs/REQUIREMENTS.md
```

Question answered:

> **What must BookQu do?**

Defines:

* functional requirements;
* business rules;
* authorization requirements;
* security requirements;
* performance requirements;
* reliability requirements;
* scalability requirements;
* usability requirements;
* acceptance criteria;
* requirement identifiers;
* requirement traceability.

Every meaningful product behavior should have a corresponding requirement.

Examples:

```text
FR-BOOKING-001
FR-SCHEDULE-001
FR-PAYMENT-001
FR-SUB-001
```

This document is the primary behavioral authority.

---

# 7. ARCHITECTURE.md

Location:

```text
/docs/ARCHITECTURE.md
```

Question answered:

> **How should BookQu be built?**

Defines:

* application architecture;
* domain boundaries;
* layer responsibilities;
* folder structure;
* tenant architecture;
* booking architecture;
* payment architecture;
* database architecture;
* frontend architecture;
* testing architecture;
* coding conventions;
* refactoring principles;
* target architecture.

This document intentionally distinguishes current architecture from target architecture.

Existing legacy code is not automatically considered the target architecture.

---

# 8. DEVELOPMENT.md

Location:

```text
/docs/DEVELOPMENT.md
```

Question answered:

> **How should development work be performed?**

Defines:

* task workflow;
* feature workflow;
* bug-fix workflow;
* refactoring workflow;
* product-change workflow;
* testing workflow;
* scope-control rules;
* Git workflow;
* definition of done;
* AI-assisted development workflow;
* documentation synchronization.

This document defines the operational development process.

---

# 9. TRACKER.md

Location:

```text
/docs/TRACKER.md
```

Question answered:

> **What is the current implementation state?**

Tracks:

* requirement;
* feature;
* implementation status;
* testing status;
* architecture status;
* technical debt;
* blockers;
* refactoring queue;
* verification queue.

The tracker does not define product behavior.

It only tracks implementation against the accepted requirements.

---

# 10. ADR Directory

Location:

```text
/docs/adr/
```

Question answered:

> **Why was an important technical decision made?**

Architecture Decision Records should be created for significant long-term decisions.

Examples:

```text
001-multi-tenancy-strategy.md
002-public-tenant-url-strategy.md
003-booking-domain-structure.md
004-payment-provider-boundary.md
```

An ADR should normally contain:

```text
Context
Decision
Alternatives Considered
Consequences
```

Do not create an ADR for trivial implementation details.

---

# 11. Archive Directory

Location:

```text
/docs/archive/
```

Contains historical documentation that is no longer authoritative.

Examples:

```text
SRS-v1.md
MVP-scope-v1.md
development-tracker-v1.md
original-requirements.md
```

Historical files are preserved because they explain how BookQu evolved.

However:

> Historical documentation must not be treated as the current product specification.

---

# 12. Which Document Should I Read?

## "What is BookQu?"

Read:

```text
PRODUCT.md
```

---

## "Who uses BookQu?"

Read:

```text
PRODUCT.md
```

---

## "What does this feature need to do?"

Read:

```text
REQUIREMENTS.md
```

---

## "What business rules apply?"

Read:

```text
REQUIREMENTS.md
```

---

## "Where should this code be placed?"

Read:

```text
ARCHITECTURE.md
```

---

## "How should this feature be implemented?"

Read:

```text
ARCHITECTURE.md
DEVELOPMENT.md
```

---

## "What is currently being worked on?"

Read:

```text
TRACKER.md
```

---

## "Why does the architecture work this way?"

Read:

```text
docs/adr/
```

---

## "How did BookQu work previously?"

Read:

```text
docs/archive/
```

---

# 13. Recommended Reading by Task

## New Feature

```text
AGENTS.md
    ↓
PRODUCT.md
    ↓
REQUIREMENTS.md
    ↓
ARCHITECTURE.md
    ↓
DEVELOPMENT.md
    ↓
TRACKER.md
```

---

## Bug Fix

```text
AGENTS.md
    ↓
REQUIREMENTS.md
    ↓
Relevant Architecture
    ↓
Existing Implementation
    ↓
Relevant Tests
    ↓
DEVELOPMENT.md
```

---

## Refactoring

```text
AGENTS.md
    ↓
REQUIREMENTS.md
    ↓
ARCHITECTURE.md
    ↓
Relevant Tests
    ↓
DEVELOPMENT.md
```

---

## Product Change

```text
AGENTS.md
    ↓
PRODUCT.md
    ↓
REQUIREMENTS.md
    ↓
ARCHITECTURE.md
    ↓
DEVELOPMENT.md
    ↓
TRACKER.md
```

---

## Architectural Decision

```text
ARCHITECTURE.md
    ↓
Relevant ADR
    ↓
DEVELOPMENT.md
```

---

# 14. Source-of-Truth Rules

Use the following rules when information conflicts.

### Product Meaning

Use:

```text
PRODUCT.md
```

---

### Required Behavior

Use:

```text
REQUIREMENTS.md
```

---

### Technical Structure

Use:

```text
ARCHITECTURE.md
```

---

### Development Process

Use:

```text
DEVELOPMENT.md
```

---

### Current Implementation Status

Use:

```text
TRACKER.md
```

---

### Historical Context

Use:

```text
archive/
```

---

### Architectural Rationale

Use:

```text
adr/
```

---

# 15. Conflict Handling

When documents or code disagree:

```text
Do not silently choose an interpretation.
```

First determine the type of conflict:

```text
Product conflict
Requirement conflict
Architecture conflict
Implementation drift
Test drift
Documentation drift
Legacy behavior
```

Then update the appropriate authoritative document before allowing the conflict to become permanent.

---

# 16. Documentation Change Principle

Documentation should be changed when the underlying product or architecture changes.

Do not update documentation merely to make it match incorrect code.

The intended flow is:

```text
Decision
    ↓
Documentation
    ↓
Implementation
    ↓
Tests
    ↓
Tracker
```

not:

```text
Code
    ↓
Assume code is correct
    ↓
Rewrite documentation to match it
```

unless the code represents an explicitly accepted product change.

---

# 17. Documentation Minimalism

Do not create a new documentation file simply because a new topic appears.

First determine whether the topic belongs in an existing document.

Use:

```text
PRODUCT.md
REQUIREMENTS.md
ARCHITECTURE.md
DEVELOPMENT.md
TRACKER.md
```

before creating a new top-level document.

Create an ADR when the topic represents a significant architectural decision.

Create a new documentation category only when the existing documents become genuinely difficult to maintain.

---

# 18. Agent Documentation Rule

AI agents should prefer reading the smallest relevant portion of the documentation needed for the task.

For a booking bug, for example:

```text
AGENTS.md
+
PRODUCT.md → Booking section
+
REQUIREMENTS.md → Booking requirements
+
ARCHITECTURE.md → Booking architecture
+
DEVELOPMENT.md → Bug-fix workflow
+
TRACKER.md → Booking status
```

The agent does not need to reinterpret unrelated modules unless the task affects them.

---

# 19. Current Development Model

BookQu follows:

```text
PRODUCT
   ↓
REQUIREMENTS
   ↓
ARCHITECTURE
   ↓
DEVELOPMENT PROCESS
   ↓
IMPLEMENTATION
   ↓
TESTS
   ↓
TRACKER
```

Architecture decisions are recorded through:

```text
ADR
```

Historical context is preserved through:

```text
ARCHIVE
```

---

# 20. Final Principle

The documentation system exists to ensure that:

> **Every contributor understands what BookQu is, what it must do, how it should be built, how it should be developed, and what has already been implemented.**

The goal is not to produce more documentation.

The goal is to prevent the project from drifting away from a shared definition again.
