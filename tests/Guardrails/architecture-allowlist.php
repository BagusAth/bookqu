<?php

/*
 * Known ADR-0012 violations per rule: file => number of occurrences.
 * Entries may only be lowered or removed (see ArchitectureRulesTest).
 * Baseline taken 2026-10-30 (BQ-025); the item that removes each group is noted.
 */

return [
    // Removed by BQ-032 (Provider outcome intake).
    'provider-sdk' => [],
    // Removed by BQ-035.
    'blade-queries' => [
        'resources/views/components/landing/pricing.blade.php' => 3,
        'resources/views/components/owner/topbar.blade.php' => 1,
        'resources/views/owner/landing-page.blade.php' => 1,
    ],
    // Removed by BQ-012 (Slot bookability).
    'slot-occupancy' => [
        'app/Actions/Booking/CreateBooking.php' => 1,
        'app/Actions/Calendar/GetOwnerCalendarData.php' => 1,
        'app/Actions/Schedule/ExportScheduleReport.php' => 1,
        'app/Actions/Schedule/GenerateScheduleReport.php' => 1,
        'app/Actions/Service/DeleteService.php' => 1,
        'app/Domain/Subscription/SubscriptionUsage.php' => 1,
        'app/Http/Controllers/Customer/BookingController.php' => 2,
        'app/Http/Controllers/Customer/BookingManageController.php' => 1,
    ],
    // Removed by BQ-033.
    'plan-name-checks' => [
        'app/Http/Controllers/Owner/OwnerLandingPageController.php' => 2,
        'app/Providers/AppServiceProvider.php' => 1,
        'resources/views/owner/landing-page.blade.php' => 1,
        'resources/views/owner/subscription.blade.php' => 5,
    ],
    // Removed by BQ-009.
    'scope-bypass-reason' => [
        'app/Actions/Booking/CancelBooking.php' => 1,
        'app/Actions/Booking/EvictStalePendingBookings.php' => 2,
        'app/Actions/Payment/ProcessPaymentWebhook.php' => 1,
        'app/Actions/Schedule/BulkCreateSchedules.php' => 2,
        'app/Actions/Schedule/DeleteBlockedDate.php' => 1,
        'app/Actions/Schedule/DeleteSchedule.php' => 1,
        'app/Actions/Schedule/GetAvailableSchedules.php' => 2,
        'app/Actions/Schedule/UpdateScheduleAvailability.php' => 1,
        'app/Actions/Service/DeleteService.php' => 1,
        'app/Actions/Service/ToggleServiceStatus.php' => 1,
        'app/Actions/Service/UpdateService.php' => 1,
        'app/Actions/Subscription/ActivateSubscription.php' => 2,
        'app/Actions/Subscription/GetSubscriptionOverview.php' => 2,
        'app/Console/Commands/CheckExpiredSubscriptions.php' => 3,
        'app/Console/Commands/ExpireBookingPayments.php' => 2,
        'app/Domain/Schedule/AvailabilityRules.php' => 2,
        'app/Domain/Schedule/ScheduleConflictRules.php' => 2,
        'app/Domain/Subscription/SubscriptionUsage.php' => 3,
        'app/Http/Controllers/Admin/AdminDashboardController.php' => 7,
        'app/Http/Controllers/Customer/BookingController.php' => 1,
        'app/Http/Controllers/Customer/BookingManageController.php' => 3,
        'app/Models/Booking.php' => 3,
        'app/Models/Payment.php' => 1,
        'app/Models/Service.php' => 4,
        'app/Services/Schedule/ScheduleAvailabilityCache.php' => 1,
        'app/Support/TenantContext.php' => 1,
    ],
    // Real writes moved to the Payment lifecycle in BQ-067; the rest are status values in responses, removed by BQ-032 (Outcome intake).
    'payment-status-writers' => [
        'app/Http/Controllers/Customer/BookingController.php' => 2,
        'app/Http/Controllers/Owner/OwnerCheckoutController.php' => 2,
    ],
];
