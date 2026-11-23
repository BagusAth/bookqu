<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Tenant slugs that would be shadowed by an application route (`/{slug}` storefront).
 */
final class ReservedTenantSlugs
{
    /** Words reserved even without a route today. */
    private const EXTRA = ['admin', 'api', 'app', 'www', 'mail', 'owner', 'manage', 'login', 'register', 'logout', 'storage', 'build', 'assets'];

    public static function contains(string $slug): bool
    {
        return in_array(strtolower($slug), self::all(), true);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $segments = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $first = strtolower(explode('/', trim($route->uri(), '/'))[0]);
            if ($first !== '' && ! str_starts_with($first, '{')) {
                $segments[] = $first;
            }
        }

        return array_values(array_unique([...self::EXTRA, ...$segments]));
    }
}
