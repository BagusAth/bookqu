# BookQu Documentation

> **Document Status:** Active
> **Purpose:** Documentation navigation and source-of-truth map
> **Last Updated:** 2026-09-30

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
├── AGENT.md
│
└── docs/
    ├── 1-README.md
    ├── 2-PRODUCT.md
    ├── 3-REQUIREMENT.md
    ├── 4-ARCHITECTURE.md
    ├── 5-DEVELOPMENT.md
    ├── 6-TRACKER.md
    │
    ├── (planned Phase C: 7-SYSTEM-DESIGN.md)
    ├── (planned Phase C: 8-OPERATIONS.md)
    └── (planned Phase C: adr/)
```

---

# 3. Documentation Authority

The active documentation hierarchy is:

```text
AGENT.md
    ↓
docs/1-README.md
    ↓
docs/2-PRODUCT.md
    ↓
docs/3-REQUIREMENT.md
    ↓
docs/4-ARCHITECTURE.md
    ↓
docs/5-DEVELOPMENT.md
    ↓
docs/6-TRACKER.md
```

Each document has a different responsibility.

---

# 4. AGENT.md

Location:

```text
/AGENT.md
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

# 5. 2-PRODUCT.md

Location:

```text
/docs/2-PRODUCT.md
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

# 6. 3-REQUIREMENT.md

Location:

```text
/docs/3-REQUIREMENT.md
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

# 7. 4-ARCHITECTURE.md

Location:

```text
/docs/4-ARCHITECTURE.md
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

# 8. 5-DEVELOPMENT.md

Location:

```text
/docs/5-DEVELOPMENT.md
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

# 9. 6-TRACKER.md

Location:

```text
/docs/6-TRACKER.md
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

# 10. Planned Technical & Operational Documents

The documentation architecture plans for the following dedicated documents (to be established in Phase C):

```text
/docs/7-SYSTEM-DESIGN.md (Planned: detailed component design, data flows, and state machines)
/docs/8-OPERATIONS.md    (Planned: deployment, environment configuration, queues, and runbooks)
/docs/adr/               (Planned: Architecture Decision Records for significant technical choices)
```

Until these documents are created, high-level architecture principles are governed by `docs/4-ARCHITECTURE.md` and workflow rules by `docs/5-DEVELOPMENT.md`.

---

# 11. Which Document Should I Read?

## "What is BookQu?"

Read:

```text
docs/2-PRODUCT.md
```

---

## "Who uses BookQu?"

Read:

```text
docs/2-PRODUCT.md
```

---

## "What does this feature need to do?"

Read:

```text
docs/3-REQUIREMENT.md
```

---

## "What business rules apply?"

Read:

```text
docs/3-REQUIREMENT.md
```

---

## "Where should this code be placed?"

Read:

```text
docs/4-ARCHITECTURE.md
```

---

## "How should this feature be implemented?"

Read:

```text
docs/4-ARCHITECTURE.md
docs/5-DEVELOPMENT.md
```

---

## "What is currently being worked on?"

Read:

```text
docs/6-TRACKER.md
```

---

## "Why does the architecture work this way?"

Read:

```text
docs/4-ARCHITECTURE.md
(and planned docs/adr/ when established)
```

---

## "How did BookQu evolve historically?"

Read:

```text
Git history (commit log, pull requests, and release tags)
```

---

# 12. Recommended Reading by Task

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
docs/5-DEVELOPMENT.md
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
Relevant Architecture (docs/4-ARCHITECTURE.md)
    ↓
Existing Implementation
    ↓
Relevant Tests
    ↓
docs/5-DEVELOPMENT.md
```

---

## Refactoring

```text
AGENT.md
    ↓
docs/3-REQUIREMENT.md
    ↓
docs/4-ARCHITECTURE.md
    ↓
Relevant Tests
    ↓
docs/5-DEVELOPMENT.md
```

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
docs/5-DEVELOPMENT.md
    ↓
docs/6-TRACKER.md
```

---

## Architectural Decision

```text
docs/4-ARCHITECTURE.md
    ↓
Relevant ADR (docs/adr/)
    ↓
docs/5-DEVELOPMENT.md
```

---

# 13. Source-of-Truth Rules

Use the following rules when information conflicts.

### Product Meaning

Use:

```text
docs/2-PRODUCT.md
```

---

### Required Behavior

Use:

```text
docs/3-REQUIREMENT.md
```

---

### Technical Structure

Use:

```text
docs/4-ARCHITECTURE.md
```

---

### Development Process

Use:

```text
docs/5-DEVELOPMENT.md
```

---

### Current Implementation Status

Use:

```text
docs/6-TRACKER.md
```

---

### Historical Evolution & Changes

Use:

```text
Git history (commits, pull requests, releases)
```

---

### Architectural Rationale

Use:

```text
docs/4-ARCHITECTURE.md
(and planned docs/adr/)
```

---

# 14. Conflict Handling

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

# 15. Documentation Change Principle

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

# 16. Documentation Minimalism

Do not create a new documentation file simply because a new topic appears.

First determine whether the topic belongs in an existing document.

Use:

```text
docs/2-PRODUCT.md
docs/3-REQUIREMENT.md
docs/4-ARCHITECTURE.md
docs/5-DEVELOPMENT.md
docs/6-TRACKER.md
```

before creating a new top-level document.

Create an ADR when the topic represents a significant architectural decision.

Create a new documentation category only when the existing documents become genuinely difficult to maintain.

---

# 17. Agent Documentation Rule

AI agents should prefer reading the smallest relevant portion of the documentation needed for the task.

For a booking bug, for example:

```text
AGENT.md
+
docs/2-PRODUCT.md → Booking section
+
docs/3-REQUIREMENT.md → Booking requirements
+
docs/4-ARCHITECTURE.md → Booking architecture
+
docs/5-DEVELOPMENT.md → Bug-fix workflow
+
docs/6-TRACKER.md → Booking status
```

The agent does not need to reinterpret unrelated modules unless the task affects them.

---

# 18. Current Development Model

BookQu follows:

```text
PRODUCT (docs/2-PRODUCT.md)
   ↓
REQUIREMENTS (docs/3-REQUIREMENT.md)
   ↓
ARCHITECTURE (docs/4-ARCHITECTURE.md)
   ↓
DEVELOPMENT PROCESS (docs/5-DEVELOPMENT.md)
   ↓
IMPLEMENTATION
   ↓
TESTS
   ↓
TRACKER (docs/6-TRACKER.md)
```

Architecture decisions are recorded through:

```text
ADR (docs/adr/)
```

Historical evolution is preserved in Git history.

---

# 19. Final Principle

The documentation system exists to ensure that:

> **Every contributor understands what BookQu is, what it must do, how it should be built, how it should be developed, and what has already been implemented.**

The goal is not to produce more documentation.

The goal is to prevent the project from drifting away from a shared definition again.
