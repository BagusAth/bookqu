<?php

/*
| Staged module registry (ADR-0011). States: released | locked | hidden.
| hidden → every route answers 404; locked → GET only, writes answer 403.
| A module missing here is hidden. Promotion is a reviewed change (ADR-0002).
*/

return [
    // Tier 3
    'vouchers' => env('MODULE_VOUCHERS', 'hidden'),
    'additional-items' => env('MODULE_ADDITIONAL_ITEMS', 'hidden'),
    'staff-resources' => env('MODULE_STAFF_RESOURCES', 'hidden'),
    'appearance' => env('MODULE_APPEARANCE', 'hidden'),
    'direct-payment' => env('MODULE_DIRECT_PAYMENT', 'hidden'),
    'assets' => env('MODULE_ASSETS', 'hidden'),
    'landing-page' => env('MODULE_LANDING_PAGE', 'hidden'),

    // Tier 2
    'integrations' => env('MODULE_INTEGRATIONS', 'locked'),
    'analytics' => env('MODULE_ANALYTICS', 'locked'),
    'schedule-report' => env('MODULE_SCHEDULE_REPORT', 'locked'),
    'balance' => env('MODULE_BALANCE', 'locked'),
];
