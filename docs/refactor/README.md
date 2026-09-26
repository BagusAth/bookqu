# BookQu Refactor Work Orders

This directory contains executable refactor instructions for AI agents.

## How To Use

Use exactly one work-order document per agent task.

Recommended sequence:

```text
REFACTOR-PLAN.md
      ↓
Select one RF document
      ↓
Read AGENTS.md
      ↓
Read relevant PRODUCT / REQUIREMENTS / ARCHITECTURE sections
      ↓
Read the complete RF document
      ↓
Execute its subtask sequence
      ↓
Test after each major subtask
      ↓
Check acceptance criteria
      ↓
Update TRACKER.md
```

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

Status changes belong in `TRACKER.md`; the RF file remains the stable instructions for the phase.
