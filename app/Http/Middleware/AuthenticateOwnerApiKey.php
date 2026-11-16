<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Modules\ModuleRelease;
use App\Domain\Subscription\EntitlementRules;
use App\Models\OwnerApiKey;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateOwnerApiKey
{
    public function __construct(private TenantContext $context) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (ModuleRelease::state('api') !== 'released') {
            return response()->json(['message' => 'Tidak ditemukan.'], 404);
        }
        $token = $request->bearerToken() ?? '';
        if (preg_match('/^bq_[0-9a-f]{64}$/D', $token) !== 1) {
            return response()->json(['message' => 'Kunci API tidak valid.'], 401);
        }

        // scope-bypass: a verified digest identifies the tenant for this stateless API request.
        $key = OwnerApiKey::withoutGlobalScopes()->where('digest', hash('sha256', $token))->whereNull('revoked_at')->first();
        if ($key === null) {
            return response()->json(['message' => 'Kunci API tidak valid.'], 401);
        }
        $bucket = 'owner-api:'.$key->id;
        if (RateLimiter::hit($bucket, 60) > 60) {
            return response()->json(['message' => 'Batas permintaan API tercapai.'], 429, ['Retry-After' => RateLimiter::availableIn($bucket)]);
        }

        $this->context->setTenantId((int) $key->idtenant);
        try {
            $tenant = $this->context->tenant();
            if ($tenant === null || ! EntitlementRules::canAccessFeature($tenant->subscription()->with('plan')->first(), 'api')['allowed']) {
                return response()->json(['message' => 'Paket ini tidak dapat mengakses API.'], 403);
            }

            $response = $next($request);
            $response->headers->set('Cache-Control', 'no-store');

            return $response;
        } finally {
            $this->context->clear();
        }
    }
}
