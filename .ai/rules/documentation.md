---
paths:
  - docs/**
  - AGENTS.md
  - .ai/rules/**
  - README.md
---

# Documentation

Context: `docs/workflow.md` §8 and §10.

## Authority
`AGENTS.md`, `docs/*.md` and ADRs change only with lead approval. Work items (`docs/work/BQ-*.md`) and domain files may be updated as part of approved work.

## One rule, one place
Do not copy a rule into a second document; link to its home. Keep IDs stable (`FR-`, `BR-`, `NFR-`, `INV-`, `BQ-`, `ADR-`).

## Current versus target
`docs/domains/` describes what exists now, with evidence; `docs/architecture.md` describes the target. Status changes need evidence.

## Boost block
Never edit inside the Laravel Boost block of `AGENTS.md`; `php artisan boost:update` rewrites it. The BookQu section above it is preserved **only if it never contains Boost's literal opening or closing tag** — Boost matches the first occurrence with a regex and would overwrite everything from there. After editing `AGENTS.md`, run `php artisan boost:update` and confirm the BookQu section is unchanged.

## Rule files
Each file in `.ai/rules/` starts with `paths:` front matter. After adding or changing globs, regenerate the index:
`php artisan tinker --execute 'app(\Laravel\Boost\Rules\RuleRepository::class)->writeIndex();'`
