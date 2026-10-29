---
paths:
  - app/Models/**
  - database/**
  - app/Traits/ClearsBookingCache.php
  - app/Services/Schedule/**
---

# Models, migrations and cache

Context: `docs/glossary.md` for column names.

## Models stay small
Relationships, casts, scopes and small helpers only. Delegate workflows to Actions. Tenant-owned models use `BelongsToTenant`.

## Migrations
Every schema change is a new migration; never edit an applied one. Before changing a column, search models, queries, views, seeders, tests and commands. Protect critical invariants with FKs, unique indexes and NOT NULL.

## Names
Existing tables and columns keep their legacy names until a dedicated migration task. New columns and new code use canonical English names from `docs/glossary.md`.

## Cache is derived
Never treat cache as the source of truth for bookings, payments, authorization or tenant identity. Cache keys include tenant and every other isolating dimension (`tenant:{id}:service:{id}:…`); booking mutations clear the affected availability keys. Do not cache secrets.
