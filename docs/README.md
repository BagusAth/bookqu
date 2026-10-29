# Documentation map

Every question has one authoritative source. Start with `AGENTS.md` (always loaded by agents), then come here.

| Question | Source | Kind |
|---|---|---|
| How must agents and contributors work? Non-negotiable rules, who approves what | `../AGENTS.md` | Authority |
| What is BookQu, who uses it, which features are released? | `product.md` | Authority |
| What must the system do? (`FR-`, `BR-`, `NFR-`, `AC-`) | `requirements.md` | Authority |
| How must the code be structured? | `architecture.md` | Authority (target) |
| How does each domain work today, which invariants (`INV-`) hold, what proves them, what is its status? | `domains/` | Current state |
| What do terms and legacy names mean? | `glossary.md` | Reference |
| How is work planned, approved, implemented, reviewed? | `workflow.md` | Process |
| How is BookQu deployed, operated and recovered? | `operations.md` | Process |
| Why was a decision made? | `adr/` | Rationale |
| What is planned, in progress or done? | `work/ROADMAP.md`, `work/BQ-*.md` | Status |
| Which rules apply to the file I am editing? | `../.ai/rules/index.md` | Rules by path |

## Rules for this documentation

- **One rule, one place.** Other documents link to it instead of repeating it.
- **Stable IDs.** `FR-`, `BR-`, `NFR-`, `AC-`, `INV-`, `BQ-`, `ADR-` are never renumbered or reused.
- **Current vs target vs status.** `domains/` describes what exists now (with evidence); `architecture.md` describes the target; `work/` holds status and plans. Never present the target as implemented.
- **Status vocabulary.** Capabilities: `Implemented`, `Partial`, `Planned`, `Deprecated`; verification and architecture status as defined in `domains/README.md`. Work items: `proposed`, `approved`, `in-progress`, `done`, `rejected`.
- **No history in active docs.** Git keeps it (the pre-2026-10-30 documents are at tag `baseline-2026-10-30`).
- **New documents** only for a long-lived responsibility no existing document can hold.
- Changes to authority documents need lead approval (`AGENTS.md` → "Who decides"). How to keep docs in sync with code: `workflow.md` §8.
