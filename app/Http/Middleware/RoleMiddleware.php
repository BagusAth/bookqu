<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = Auth::user();

        if (!$user || ($roles !== [] && !in_array($user->role, $roles, true))) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
