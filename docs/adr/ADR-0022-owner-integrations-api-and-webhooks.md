# ADR-0022: Owner integrations, public API and outbound webhooks

> **Status:** Proposed
> **Date:** 2026-11-04
> **Decider:** Tech Lead — Product Lead for the product scope (D-019)
> **Work item:** BQ-126 (specification); implementation items follow in epic E6

## Context

D-019 lets every plan connect Google Calendar, Google Analytics and Meta Pixel, and gives Pro tenants API keys and webhooks (FR-INTEGRATION-001/002, FR-API-001/002).

- **Today:** BookQu has no outbound integration owned by a tenant, no public API and no outbound webhooks. The only inbound integrations are payment webhooks (ADR-0007).
- **Tenancy:** every new path must keep tenant isolation (INV-TEN-01), keep secrets out of logs (`AGENTS.md` rule 12), and run its external calls outside transactions (ADR-0005 decision 4).
- **Hosting:** background work runs on shared hosting through the minute scheduler and a short queue worker (ADR-0013).

## Options

1. **Call external services synchronously from the Actions.** Rejected: external latency and failures would enter booking transactions.
2. **One integration seam with one adapter per provider, fed by domain events after commit, with every external call in a queued job.** Chosen.

## Decision

1. **Integration seam.**
   - `App\Contracts\Integrations\OwnerIntegration` defines connect, test, disconnect and `deliver(event)`.
   - The adapters live in `app/Infrastructure/Integrations/`: Google Calendar and webhooks.
   - Google Analytics and Meta Pixel are not adapters. They are tenant settings: measurement ids, validated by format, that the public page renders through the existing safe output rules.
2. **Credentials.**
   - Google Calendar uses OAuth with the minimum calendar scope. Tokens are stored per tenant in an `owner_integrations` table with encrypted casts.
   - Secrets are never returned to the browser after saving and never logged (FR-INTEGRATION-002).
   - Disconnecting revokes the token at the provider and deletes it.
3. **Events after commit.**
   - Booking Actions and the Payment lifecycle emit domain events after commit, carrying only ids:
     - `booking.created`, `booking.paid`, `booking.rescheduled`, `booking.cancelled`, `booking.completed`, `booking.no_show`;
     - `refund.decided`, `refund.processed`.
   - A listener queues one delivery job per connected integration.
   - The job runs inside the tenant-context runner (ADR-0010 decision 4), reloads the records, and calls the adapter.
   - A failure never affects the booking.
4. **Google Calendar is one-way.** BookQu writes, updates and deletes events in the owner's calendar. Changes made in Google are not read back.
5. **API keys (Pro).**
   - The key is a random secret shown once, and stored as a hash with a visible prefix.
   - A key belongs to one tenant and authorises only that tenant.
   - The first release is read-only: services, schedules with availability, bookings, customers of the tenant.
   - Requests are rate-limited per key, and every request is authorised again through the tenant context and the plan gate.
   - Revoking a key stops it at the next request.
6. **Webhooks (Pro).**
   - **Endpoints:** HTTPS URLs on public hosts. URLs that resolve to private, loopback or link-local addresses are refused at save time and again at delivery time.
   - **Signing:** each endpoint has its own secret. Every delivery is signed with HMAC-SHA256 over the raw body and a timestamp, and carries an event id for idempotency.
   - **Retries:** with backoff, up to a fixed limit. After the limit the delivery stops and the owner is notified.
   - **Log:** the delivery log keeps status code and timing. It keeps no secret and no body beyond the event id.
7. **Plan gates.** API and webhooks require the Pro feature through `EntitlementRules`. All integrations sit behind the module gate until released (ADR-0021).

## Consequences

- **Pattern:** a new outbound pattern with its own architecture check. Provider SDKs are used only in `app/Infrastructure/Integrations/`.
- **Queue load:** the queue handles more jobs on shared hosting. Deliveries are small and idempotent, and their throughput is monitored with BQ-021.
- **Tests:**
  - adapters through fakes;
  - SSRF refusal;
  - signature verification;
  - retries;
  - key scope and revocation;
  - that a booking succeeds when an integration fails.
- **Docs:** `.ai/rules/tenancy-security.md` covers integration secrets. A domain file for integrations is created with the implementation.
