<?php

declare(strict_types=1);

/*
| BookQu support contacts shown on the owner Bantuan page (owner-portal.md §8.17, FR-HELP-001).
| They come from the environment so no contact is hardcoded in views (AC-BNT-01).
*/

return [
    'whatsapp' => env('SUPPORT_WHATSAPP'),
    'email' => env('SUPPORT_EMAIL'),
    'hours' => env('SUPPORT_HOURS', 'Senin–Jumat, 09.00–17.00 WIB'),
];
