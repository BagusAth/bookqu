<?php

namespace App\Http\Middleware;

use App\Actions\CustomerAccess\CustomerAccessSession;
use App\Domain\CustomerAccess\AccessOperation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerAccessMiddleware
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        $canonicalHost = parse_url(config('app.url'), PHP_URL_HOST);
        abort_unless($request->getHost() === $canonicalHost, 404);
        abort_if(! app()->environment('local', 'testing') && ! $request->isSecure(), 404);
        $context = app(CustomerAccessSession::class)->resolve($request);
        abort_unless($context->allows(AccessOperation::from($operation)), 404);
        $order = $request->route('payment') ?? $request->route('order_id');
        if ($order !== null) {
            abort_unless($context->payment !== null && $context->payment->order_id === (string) $order, 404);
        }
        $code = $request->route('booking_code');
        if ($code !== null) {
            abort_unless($context->bookings->contains('booking_code', $code), 404);
        }
        $request->attributes->set('customer_access', $context);

        return $next($request);
    }
}
