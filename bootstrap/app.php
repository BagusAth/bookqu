<?php

use App\Console\Commands\CheckExpiredSubscriptions;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CustomerAccessHeaders;
use App\Http\Middleware\CustomerAccessMiddleware;
use App\Http\Middleware\EnsureOwnerProfileComplete;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

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
        $middleware->web(prepend: [CustomerAccessHeaders::class]);
        $middleware->alias([
            'customer.access' => CustomerAccessMiddleware::class,
            'tenant' => TenantMiddleware::class,
            'role' => RoleMiddleware::class,
            'owner.profile' => EnsureOwnerProfileComplete::class,
            'subscription' => CheckSubscription::class,
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
    })->create();
