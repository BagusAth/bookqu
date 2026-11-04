<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Booking\CheckoutDraftSession;
use App\Actions\Tenant\GetStorefrontTenant;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\CustomerBookingRoutes;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Support\Facades\Cache;

abstract class StorefrontBookingController extends Controller
{
    use ClearsBookingCache;

    public function __construct(protected CheckoutDraftSession $drafts) {}

    protected function restartBooking(string $slug_usaha)
    {
        session()->reflash();

        return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
    }

    protected function resolveTenant(string $slug_usaha): ?Tenant
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return $tenantId === null ? null : app(GetStorefrontTenant::class)->execute($tenantId);
    }

    protected function resolveService(int $tenantId, int $serviceId): ?Service
    {
        $serviceData = Cache::remember($this->getServiceCacheKey($tenantId, $serviceId), now()->addSeconds(3600), function () use ($tenantId, $serviceId) {
            // scope-bypass: filtered by the resolved storefront tenant
            return Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('idtenant', $tenantId)
                ->where('is_active', true)
                ->first()?->getAttributes();
        });

        if (! $serviceData) {
            return null;
        }

        return Service::hydrate([$serviceData])->first();
    }
}
