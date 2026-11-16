<?php

/*
| Module registry (ADR-0011, ADR-0023). States: released | locked | hidden.
| hidden → every route answers 404; locked → GET only, writes answer 403.
| A module missing here is hidden. Every module is released (ADR-0023); the env value is an
| emergency switch per environment. Plan limits stay in EntitlementRules.
*/

return [
    'units' => env('MODULE_UNITS', 'released'),
    'capacity' => env('MODULE_CAPACITY', 'released'),
    'no-show' => env('MODULE_NO_SHOW', 'released'),
    'promos' => env('MODULE_PROMOS', 'released'),
    'add-ons' => env('MODULE_ADD_ONS', 'released'),
    'team' => env('MODULE_TEAM', 'released'),
    'booking-page' => env('MODULE_BOOKING_PAGE', 'released'),
    'reports' => env('MODULE_REPORTS', 'released'),
    'refunds' => env('MODULE_REFUNDS', 'released'),
    'help' => env('MODULE_HELP', 'released'),
    'api' => env('MODULE_API', 'released'),
    'integrations' => env('MODULE_INTEGRATIONS', 'released'),
    // Released by BQ-015 (lead decision 2026-11-01: withdraw in v1, fee 0%)
    'balance' => env('MODULE_BALANCE', 'released'),
];
