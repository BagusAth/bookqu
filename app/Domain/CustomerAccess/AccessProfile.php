<?php

namespace App\Domain\CustomerAccess;

enum AccessProfile: string
{
    case Checkout = 'checkout';
    case Management = 'management';
}
