# Owner integrations

The settings routes are gated by the Integrasi module, the active plan and the member's integration permission. The storefront publishes only validated measurement IDs and only while that module and entitlement are active.

Google Calendar uses OAuth authorization code with PKCE and one-use state bound to the tenant and member. Access and refresh tokens are encrypted in owner_integrations. The settings response exposes connection status only. Disconnect revokes the provider grant and clears its credentials.

Booking events carry the tenant id, booking id and event name after commit. The queued adapter resolves the current booking in its tenant and writes one event per confirmed slot to the primary calendar. Rescheduling updates that event and cancellation deletes it. Provider failure is handled by the job after the booking transaction.

Evidence: Owner/OwnerIntegrationsTest. Configure GOOGLE_CALENDAR_CLIENT_ID and GOOGLE_CALENDAR_CLIENT_SECRET per environment. Register the callback at /owner/settings/integrations/calendar/callback under the platform APP_URL.
