<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\RedirectResponse;

final class CustomerBookingRoutes
{
    /**
     * Determine whether the current request is coming from a custom tenant domain
     * (i.e. NOT the main BookQu platform domain).
     */
    private static function isCustomDomain(): bool
    {
        $host = request()->getHost();
        $mainHost = parse_url(config('app.url'), PHP_URL_HOST) ?? 'bookqu.my.id';

        return $host !== $mainHost
            && $host !== '127.0.0.1'
            && $host !== 'localhost'
            && !str_contains($host, 'bookqu.test');
    }

    /**
     * Resolve the correct named route depending on the current domain context.
     * Custom-domain requests use the `customer.booking.*` names (domain-scoped).
     * Slug-based requests use the `customer.booking.slug.*` names (path-prefixed).
     */
    public static function name(string $routeName): string
    {
        return self::isCustomDomain()
            ? $routeName
            : 'customer.booking.slug.' . str_replace('customer.booking.', '', $routeName);
    }

    /**
     * Generate an absolute URL for the given logical route name.
     * On custom domains the `custom_domain` route parameter is injected automatically.
     */
    public static function url(string $routeName, mixed $parameters = [], bool $absolute = true): string
    {
        return route(self::name($routeName), self::buildParameters($parameters), $absolute);
    }

    /**
     * Return a redirect response to the given logical route name.
     * Equivalent to redirect()->route(...) but domain-aware.
     *
     * @param  mixed  $parameters  A scalar slug, a positional array, or an associative array.
     */
    public static function route(string $routeName, mixed $parameters = []): RedirectResponse
    {
        return redirect()->route(self::name($routeName), self::buildParameters($parameters));
    }

    /**
     * Normalise route parameters for URL generation.
     *
     * On custom-domain routes the route is defined with `Route::domain('{custom_domain}')`,
     * so Laravel needs `custom_domain` to be present when generating the URL.
     * We inject the current host automatically and strip any `slug_usaha` key from
     * associative arrays (the slug is not part of the custom-domain path).
     *
     * On slug-based routes the first positional parameter is always `slug_usaha`.
     */
    private static function buildParameters(mixed $parameters): mixed
    {
        if (self::isCustomDomain()) {
            $host = request()->getHost();

            if (is_array($parameters)) {
                // Positional array: strip the leading slug_usaha element (first item) and
                // prepend the custom_domain, keeping remaining path parameters in order.
                if (array_is_list($parameters)) {
                    // First element may be the slug — remove it; domain replaces it.
                    $rest = array_slice($parameters, 1);
                    return array_merge(['custom_domain' => $host], $rest);
                }

                // Associative array: remove slug_usaha key, inject custom_domain.
                unset($parameters['slug_usaha']);
                return array_merge(['custom_domain' => $host], $parameters);
            }

            // Scalar (the slug itself): just inject custom_domain.
            return ['custom_domain' => $host];
        }

        // Slug-based: pass parameters as-is; the first positional value is slug_usaha.
        return $parameters;
    }
}