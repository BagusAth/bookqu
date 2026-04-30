<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  array<int, string>  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Akses Ditolak. Anda bukan '.implode(' atau ', $roles));
        }

        return $next($request);
    }
}
