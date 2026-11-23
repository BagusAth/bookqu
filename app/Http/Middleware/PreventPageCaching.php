<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Storefront booking pages carry a CSRF token and session-bound selections. Without
 * `no-store` the browser's Back button replays a stored copy whose token the session
 * already rotated, and the next submit fails with 419.
 */
class PreventPageCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
