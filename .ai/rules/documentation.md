---
paths:
  - docs/**
  - AGENTS.md
  - .ai/rules/**
  - README.md
---

# Documentation

Context: `docs/workflow.md` §9 and §11.

## Authority
`AGENTS.md`, `docs/*.md`, `.ai/rules` and ADRs change only with Tech Lead approval (heavy lane). Work items (`docs/work/BQ-*.md`), domain files and `README.md` may be updated as part of the task they describe. Completed work items and accepted ADRs are historical records: do not rewrite them; supersede or amend instead.

## Language and roles
Authority docs, rules and ADRs are English; `README.md`, release notes and UI copy are Indonesian. Name roles (Product Lead, Tech Lead, Developer), not people, outside the roles table in `AGENTS.md`.

## One rule, one place
Do not copy a rule into a second document; link to its home. Keep IDs stable (`FR-`, `BR-`, `NFR-`, `INV-`, `BQ-`, `ADR-`).

## Current versus target
`docs/domains/` describes what exists now, with evidence; `docs/architecture.md` describes the target. Status changes need evidence.

## Boost block
Never edit inside the Laravel Boost block of `AGENTS.md`; `php artisan boost:update` rewrites it. The BookQu section above it is preserved **only if it never contains Boost's literal opening or closing tag** — Boost matches the first occurrence with a regex and would overwrite everything from there. After editing `AGENTS.md`, run `php artisan boost:update` and confirm the BookQu section is unchanged.

## Rule files
Each file in `.ai/rules/` starts with `paths:` front matter. After adding or changing globs, regenerate the index:
`php artisan tinker --execute 'app(\Laravel\Boost\Rules\RuleRepository::class)->writeIndex();'`
