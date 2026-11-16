<?php

declare(strict_types=1);

/*
| Custom domains of Pro businesses (BQ-154, D-005, owner-portal.md §8.9). A domain is connected when
| its CNAME points to `cname_target` or one of its A records is in `a_records`. Both are set per
| environment by the Tech Lead; with neither set, no domain is ever marked connected.
*/

return [
    'cname_target' => env('CUSTOM_DOMAIN_CNAME_TARGET', ''),
    'a_records' => array_values(array_filter(array_map('trim', explode(',', (string) env('CUSTOM_DOMAIN_A_RECORDS', ''))))),
];
