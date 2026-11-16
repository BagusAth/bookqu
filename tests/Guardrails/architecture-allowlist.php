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
    // The top bar no longer queries (BQ-128: GetOwnerShell prepares the shell); the landing
    // builder view was replaced by the Halaman Booking sections (BQ-154).
    'blade-queries' => [],
    // Removed by BQ-012 (Slot bookability).
    'slot-occupancy' => [
        'app/Actions/Booking/CreateBooking.php' => 1,
        'app/Domain/Subscription/SubscriptionUsage.php' => 1,
    ],
    // Removed by BQ-033.
    'plan-name-checks' => [],
    // Emptied by BQ-009: every bypass now states its reason.
    'scope-bypass-reason' => [],
    // Real writes moved to the Payment lifecycle in BQ-067; the rest are status values in responses, removed by BQ-032 (Outcome intake).
    'payment-status-writers' => [
        'app/Http/Controllers/Owner/OwnerCheckoutController.php' => 2,
    ],
    // Added empty by BQ-089: views assign only fixed markup to innerHTML.
    'inner-html-data' => [],
    // Added empty by BQ-129: owner views use the confirm dialog and toast, not native dialogs.
    'native-dialogs' => [],
];
