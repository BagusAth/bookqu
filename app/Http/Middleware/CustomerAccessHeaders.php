<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CustomerAccessHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('access', 'access/*', 'manage/*', '*/booking/payment/*', 'booking/payment/*')) {
            return $next($request);
        }

        try {
            $response = $next($request);
        } catch (HttpExceptionInterface $exception) {
            if (! in_array($exception->getStatusCode(), [404, 429], true)) {
                throw $exception;
            }
            $response = response($exception->getStatusCode() === 404 ? 'Halaman tidak tersedia.' : 'Terlalu banyak permintaan.', $exception->getStatusCode(), $exception->getHeaders());
        }
        if ($response->getStatusCode() === 404) {
            $response = response('Halaman tidak tersedia.', 404);
        }
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
