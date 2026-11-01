<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CustomerAccessHeaders
{
    private const NOT_FOUND = 'Halaman tidak tersedia. Tautan mungkin sudah kedaluwarsa atau sesi akses telah berakhir. Buka kembali tautan dari email konfirmasi reservasi Anda.';

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
            $response = response($exception->getStatusCode() === 404 ? self::NOT_FOUND : 'Terlalu banyak permintaan.', $exception->getStatusCode(), $exception->getHeaders());
        }
        if ($response->getStatusCode() === 404) {
            $response = response(self::NOT_FOUND, 404);
        }
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
