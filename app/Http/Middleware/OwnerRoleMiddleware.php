<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OwnerRoleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('owner.login');
        }

        $pengguna = Auth::user();
        if (!$pengguna || $pengguna->role !== 'owner') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('owner.login')
                ->with('gagal', 'Akun tidak memiliki akses owner.');
        }

        return $next($request);
    }
}
