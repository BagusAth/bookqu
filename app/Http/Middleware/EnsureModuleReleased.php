<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side release gate for staged modules (ADR-0011): `module:<name>`.
 */
class EnsureModuleReleased
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $state = config('modules.'.$module, 'hidden');

        if ($state === 'released') {
            return $next($request);
        }

        if ($state === 'locked' && $request->isMethodSafe()) {
            return $next($request);
        }

        abort($state === 'locked' ? 403 : 404);
    }
}
