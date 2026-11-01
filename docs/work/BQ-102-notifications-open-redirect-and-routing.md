---
id: BQ-102
title: Open redirect in mark-as-read and missing deep link routing in notifications
type: bug-fix
status: proposed
priority: P1
phase: 8
domain: notifications
requirements: [FR-NOTIFICATION-001, FR-NOTIFICATION-002, NFR-SEC-006, NFR-SEC-008]
invariants: []
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: []
---

# BQ-102: Open redirect in mark-as-read and missing deep link routing in notifications

Found during the owner UI deep audit on 2026-11-02 in `OwnerNotificationController` and `/owner/notifications`.

## Problem

### 1. Open Redirect Vulnerability in Notification Read Action (CWE-601)
In `app/Http/Controllers/Owner/OwnerNotificationController.php:94-101`:
```php
public function markAsRead(Request $request, string $id)
{
    $notification = $request->user()->notifications()->findOrFail($id);
    $notification->markAsRead();

    $redirectUrl = $request->input('redirect') ?: back()->getTargetUrl();

    return redirect($redirectUrl);
}
```
The controller accepts an arbitrary `redirect` parameter from user input and immediately issues a redirect without validating that the target URL belongs to the application's domain. An external attacker or malicious link could supply `?redirect=https://evil.example.com` or protocol-relative URLs (`//attacker.com`), redirecting an authenticated business owner to an external phishing or credential-harvesting site.

### 2. Missing Deep Links for Actionable Notifications
In `resources/views/owner/notifications.blade.php:208`:
- When rendering booking notifications containing `$notification->data['booking_code']`, the "Lihat Detail" CTA links generically to `route('owner.bookings')` without passing the booking code as a query filter.
- As a result, the owner is dropped onto the general bookings index and must manually find or copy-paste the booking code to locate the customer's reservation.

### 3. Native Confirmation on Bulk Notification Deletion
In `resources/views/owner/notifications.blade.php:234`:
Bulk clearing of notifications triggers a browser-native `confirm()` popup, bypassing the standard `components/owner/confirm-modal.blade.php` component and violating the UI consistency guidelines established in BQ-092.

## Plan

1. Sanitize and validate the `redirect` URL in `OwnerNotificationController::markAsRead`:
   - Reject external schemes/hosts and only allow relative local paths or URLs matching the application host (e.g. using `Redirector::to()` with local validation or `url()->isValidUrl()` within the trusted domain).
2. Update notification item action links in `notifications.blade.php` to pass `['katakunci' => $code]` to `route('owner.bookings', ...)` when a booking code exists, landing the owner directly on the targeted booking.
3. Replace native `confirm()` on notification clearing with the shared `confirm-modal` component.

## Invariants at risk

None. Notification ownership (`$request->user()->notifications()`) remains strictly isolated per user.

## Tests

- Security test: submitting `POST /owner/notifications/{id}/read?redirect=https://evil.example.com` rejects external host and falls back to a safe internal route (e.g. `route('owner.notifications')`).
- Security test: local relative path `?redirect=/owner/bookings` is honored safely.
- Feature test: clicking notification action link for a booking directs to `route('owner.bookings', ['katakunci' => $code])`.

## Docs to update

`docs/domains/notifications.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
