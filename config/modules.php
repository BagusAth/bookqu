<?php

/*
| Staged module registry (ADR-0011). States: released | locked | hidden.
| hidden → every route answers 404; locked → GET only, writes answer 403.
| A module missing here is hidden. Promotion is a reviewed change (ADR-0002).
*/

return [
    // Redesign capabilities stay hidden until their implementation is audited (ADR-0021).
    'units' => 'hidden',
    'promos' => 'hidden',
    'add-ons' => 'hidden',
    'team' => 'hidden',
    'booking-page' => 'hidden',
    'api' => 'hidden',
    'reports' => 'hidden',
    'refunds' => 'hidden',
    'help' => 'hidden',
    'capacity' => 'hidden',
    'no-show' => 'hidden',

    // Tier 3
    'vouchers' => env('MODULE_VOUCHERS', 'hidden'),
    'additional-items' => env('MODULE_ADDITIONAL_ITEMS', 'hidden'),
    'assets' => env('MODULE_ASSETS', 'hidden'),

    // Tier 2
    'integrations' => env('MODULE_INTEGRATIONS', 'hidden'),
    // Released by BQ-015 (lead decision 2026-11-01: withdraw in v1, fee 0%)
    'balance' => env('MODULE_BALANCE', 'released'),
];
