<?php

namespace App\Actions\CustomerAccess;

use App\Domain\CustomerAccess\AccessContext;
use Illuminate\Http\Request;

final class CustomerAccessSession
{
    public function establish(Request $request, AccessContext $context): void
    {
        abort_if(config('session.driver') === 'cookie', 503);
        $request->session()->regenerate();
        $request->session()->put('customer_access', [
            'grant_id' => (int) $context->grant->id,
            'started_at' => now()->timestamp,
            'last_seen_at' => now()->timestamp,
        ]);
    }

    public function resolve(Request $request): AccessContext
    {
        $state = $request->session()->get('customer_access');
        abort_unless(is_array($state) && isset($state['grant_id'], $state['started_at'], $state['last_seen_at']), 404);
        abort_if(config('session.driver') === 'cookie', 503);
        abort_if(now()->timestamp - $state['last_seen_at'] >= 1800 || now()->timestamp - $state['started_at'] >= 43200, 404);
        $context = app(ResolveCustomerAccess::class)->execute((int) $state['grant_id']);
        $request->session()->put('customer_access.last_seen_at', now()->timestamp);

        return $context;
    }
}
