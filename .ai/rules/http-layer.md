---
paths:
  - app/Http/Controllers/**
  - app/Http/Requests/**
  - routes/**
---

# Controllers, requests and routes

Context: `docs/architecture.md` → Layers.

## Thin controllers
A controller method validates (Form Request), authorizes, calls one Action, and returns a response. Business rules, queries for domain decisions and multi-step workflows belong in `app/Actions` and `app/Domain`.

## Do not grow large controllers
When touching `Customer/BookingController` (1,034 lines) or another large controller, extract the logic you need into an Action instead of adding to the method.

## Form Requests validate input only
Field rules live in `app/Http/Requests/<Area>/`. Rules such as "slot occupied" or "quota reached" stay in the domain.

## Declarative routes
Routes declare method, URI, controller, middleware and name. No logic in closures. Names follow `area.resource.action` (`owner.bookings.status`). Do not add more aliases of existing routes.

## Storefront URLs
Build customer storefront URLs with `App\Support\CustomerBookingRoutes` so slug and custom-domain routing both work.
