<?php

use App\Console\Commands\CheckExpiredSubscriptions;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CustomerAccessHeaders;
use App\Http\Middleware\CustomerAccessMiddleware;
use App\Http\Middleware\EnsureModuleReleased;
use App\Http\Middleware\EnsureOwnerProfileComplete;
use App\Http\Middleware\PreventPageCaching;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Routing\Route;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        CheckExpiredSubscriptions::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        app()->booted(function () use ($middleware): void {
            $trustedProxies = trim((string) config('app.trusted_proxies', ''));
            if ($trustedProxies !== '') {
                $middleware->trustProxies(at: $trustedProxies);
            }
        });

        $middleware->web(prepend: [CustomerAccessHeaders::class]);
        $middleware->alias([
            'customer.access' => CustomerAccessMiddleware::class,
            'tenant' => TenantMiddleware::class,
            'role' => RoleMiddleware::class,
            'owner.profile' => EnsureOwnerProfileComplete::class,
            'subscription' => CheckSubscription::class,
            'module' => EnsureModuleReleased::class,
            'no-store' => PreventPageCaching::class,
        ]);

        $middleware->priority([
            EncryptCookies::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            AuthenticatesRequests::class,
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
            Authenticate::class,
            CustomerAccessMiddleware::class,
            TenantMiddleware::class,
            SubstituteBindings::class,
            Authorize::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'midtrans/webhook',
            'midtrans/notification',
            'api/webhooks/singapay/*',
            'api/webhooks/singapay/transaction',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['credential']);

        // An expired CSRF token (stale tab, Back button, idle session) never shows the bare
        // "Page Expired" page on the storefront: the customer returns to the form with a hint.
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi halaman telah berakhir. Muat ulang halaman lalu coba lagi.'], 419);
            }
            $route = $request->route();
            if (! $route instanceof Route || ! str_starts_with((string) $route->getName(), 'customer.booking.')) {
                return null;
            }
            $referer = (string) $request->headers->get('referer');
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $sameOrigin = ! str_contains($referer, '\\') && ($refererHost === null
                ? str_starts_with($referer, '/') && ! str_starts_with($referer, '//')
                : $refererHost === $request->getHost());
            $target = $sameOrigin ? $referer : '/'.ltrim((string) $route->parameter('slug_usaha', ''), '/');

            return redirect()->to($target)
                ->withInput($request->except('_token'))
                ->with('error', 'Halaman ini sudah kedaluwarsa, misalnya karena dibuka lewat tombol Kembali atau dibiarkan terlalu lama. Silakan periksa pilihan Anda lalu lanjutkan lagi.');
        });
    })->create();
