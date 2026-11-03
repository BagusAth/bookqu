---
paths:
  - app/Actions/**
  - app/Domain/**
  - app/Notifications/**
  - app/Mail/**
  - app/Console/Commands/**
---

# Actions, domain rules, notifications and commands

Context: `docs/architecture.md` → Layers.

## Actions
One Action = one meaningful operation (`CreateBooking`, `ExpirePayment`) with a `execute(...)` entry point, defined inputs, predictable errors and its own transaction boundary. No generic "manager" or "helper" actions. Search `app/Actions` before creating one.

## Domain rules
`app/Domain/<Area>` holds pure business rules (states, transitions, calculations, policies) with no HTTP or view dependencies. Every critical rule has exactly one home here.

## Transactions and external calls
Keep multi-record changes in one transaction. Do not call payment gateways, mail or other external services inside it; do that after commit.

## Notifications and mail
Send after commit, wrapped so failure cannot corrupt the main operation. New mail and notifications should be queueable (`ShouldQueue`) — see BQ-013.

## Scheduled commands
Commands find the records and delegate to Actions/domain rules; they do not contain their own copy of the rule.
