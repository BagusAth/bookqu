<?php

namespace App\Actions\CustomerAccess;

use App\Domain\CustomerAccess\AccessContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The newest grant lives in `customer_access`; up to four earlier live grants stay in
 * `customer_access_previous`, so a second booking in the same browser does not lock the
 * customer out of the first payment, invoice or management page.
 */
final class CustomerAccessSession
{
    private const PREVIOUS = 'customer_access_previous';

    public function establish(Request $request, AccessContext $context): void
    {
        abort_if(config('session.driver') === 'cookie', 503);
        $grantId = (int) $context->grant->id;
        $previous = array_values(array_filter(
            [$request->session()->get('customer_access'), ...(array) $request->session()->get(self::PREVIOUS, [])],
            fn (mixed $state): bool => $this->live($state) && (int) $state['grant_id'] !== $grantId,
        ));
        $request->session()->regenerate();
        $request->session()->put('customer_access', [
            'grant_id' => $grantId,
            'started_at' => now()->timestamp,
            'last_seen_at' => now()->timestamp,
        ]);
        $request->session()->put(self::PREVIOUS, array_slice($previous, 0, 4));
    }

    /**
     * The newest live grant that `$accepts` (operation and subject); every grant is re-validated.
     *
     * @param  (callable(AccessContext): bool)|null  $accepts
     */
    public function resolve(Request $request, ?callable $accepts = null): AccessContext
    {
        abort_if(config('session.driver') === 'cookie', 503);
        $candidates = [$request->session()->get('customer_access'), ...(array) $request->session()->get(self::PREVIOUS, [])];
        foreach ($candidates as $index => $state) {
            if (! $this->live($state)) {
                continue;
            }
            try {
                $context = app(ResolveCustomerAccess::class)->execute((int) $state['grant_id']);
            } catch (HttpExceptionInterface) {
                continue; // revoked, expired, drifted or another tenant's grant
            }
            if ($accepts !== null && ! $accepts($context)) {
                continue;
            }
            $request->session()->put($index === 0 ? 'customer_access.last_seen_at' : self::PREVIOUS.'.'.($index - 1).'.last_seen_at', now()->timestamp);

            return $context;
        }
        abort(404);
    }

    private function live(mixed $state): bool
    {
        return is_array($state) && isset($state['grant_id'], $state['started_at'], $state['last_seen_at'])
            && now()->timestamp - $state['last_seen_at'] < 1800 && now()->timestamp - $state['started_at'] < 43200;
    }
}
