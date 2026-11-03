<?php

declare(strict_types=1);

return [
    'min_amount' => (int) env('PAYOUT_MIN_AMOUNT', 10000),
    'max_open' => env('PAYOUT_MAX_OPEN') !== null ? (int) env('PAYOUT_MAX_OPEN') : 1,
    'max_amount_per_request' => env('PAYOUT_MAX_AMOUNT_PER_REQUEST') !== null ? (int) env('PAYOUT_MAX_AMOUNT_PER_REQUEST') : null,
    'max_requests_per_day' => env('PAYOUT_MAX_REQUESTS_PER_DAY') !== null ? (int) env('PAYOUT_MAX_REQUESTS_PER_DAY') : null,
    'max_accounts' => (int) env('PAYOUT_MAX_ACCOUNTS', 3),
];
