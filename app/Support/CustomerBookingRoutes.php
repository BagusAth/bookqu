<?php

declare(strict_types=1);

namespace App\Support;

final class CustomerBookingRoutes
{
    public static function url(string $routeName, mixed $parameters = [], bool $absolute = true): string
    {
        return route(self::name($routeName), $parameters, $absolute);
    }

    public static function name(string $routeName): string
    {
        $host = request()->getHost();
        $isCustomDomain = $host !== '127.0.0.1'
            && $host !== 'localhost'
            && !str_contains($host, 'bookqu.test');

        return $isCustomDomain
            ? $routeName
            : 'customer.booking.slug.' . str_replace('customer.booking.', '', $routeName);
    }
}