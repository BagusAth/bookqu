# Owner integrations

The settings routes are gated by the Integrasi module, the active plan and the member's integration permission. The storefront publishes only validated measurement IDs and only while that module and entitlement are active.

Google Calendar uses OAuth authorization code with PKCE and one-use state bound to the tenant and member. Access and refresh tokens are encrypted in owner_integrations. The settings response exposes connection status only. Disconnect revokes the provider grant and clears its credentials.

Booking events carry the tenant id, booking id and event name after commit. The queued adapter resolves the current booking in its tenant and writes one event per confirmed slot to the primary calendar. Rescheduling updates that event and cancellation deletes it. Provider failure is handled by the job after the booking transaction.

Evidence: Owner/OwnerIntegrationsTest. Configure GOOGLE_CALENDAR_CLIENT_ID and GOOGLE_CALENDAR_CLIENT_SECRET per environment. Register the callback at /owner/settings/integrations/calendar/callback under the platform APP_URL.

## API and webhooks

The hidden API module and the Pro entitlement gate owner management and API access. The read-only API exposes paginated services, schedules, bookings and customers under `/api/v1`. Each bearer key belongs to one tenant, is stored as a SHA-256 digest, can be revoked and has a limit of 60 requests per minute.

An endpoint receives booking and refund events after commit. The JSON fields are `id`, `event` and `subject_id`. The receiver can deduplicate using `X-BookQu-Event-Id` and verify `X-BookQu-Signature` as `sha256=` followed by the HMAC-SHA256 hex digest of the value in `X-BookQu-Timestamp`, a dot and the raw HTTP body. Each endpoint has its own encrypted signing secret. Keys and secrets are shown once at creation.

HTTPS destinations are checked at creation and again before each send. The client pins a public address from DNS, disables redirects and proxies, and discards response bodies. The worker makes up to five attempts with backoff and notifies the owner when delivery is exhausted. Delivery metadata expires after 30 days.

Evidence: `Tests\Feature\Owner\ApiAndWebhooksTest`.
