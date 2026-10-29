---
paths:
  - app/Domain/Subscription/**
  - app/Actions/Subscription/**
  - app/Http/Middleware/CheckSubscription.php
  - app/Http/Controllers/Owner/OwnerSubscriptionController.php
  - app/Console/Commands/CheckExpiredSubscriptions.php
  - app/Providers/AppServiceProvider.php
---

# Subscription and entitlement

Context: `docs/domains/subscription.md`.

## Entitlement in one place
Decide feature access and limits only with `EntitlementRules` (backed by `PlanCapability`, `SubscriptionState`, `SubscriptionUsage`). Never compare plan names (`str_contains($plan->namapaket, 'pro')`) in controllers, views or providers (INV-SUB-01).

## Gate routes with the middleware
Feature-gated routes use `subscription:<feature>`; the middleware must deny when no tenant or subscription is found.

## Quota inside the booking transaction
Monthly booking quota is enforced atomically in `CreateBooking`; earlier checks in the UI are hints only.

## State changes through SubscriptionState
Use `SubscriptionState` constants and transitions; scheduled expiry calls domain rules instead of raw status updates.
