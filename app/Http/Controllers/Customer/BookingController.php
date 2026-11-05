<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Booking\StorefrontCheckoutState;
use App\Domain\Subscription\EntitlementRules;
use App\Models\Service;
use App\Models\Subscription;
use App\Support\CustomerBookingRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class BookingController extends StorefrontBookingController
{
    public function showProgramSelection(?string $slug_usaha = null)
    {
        $tenant = $this->resolveTenant((string) ($slug_usaha ?? request()->route('slug_usaha')));

        if (! $tenant) {
            abort(404);
        }

        $servicesData = Cache::remember($this->getActiveServicesCacheKey($tenant->id), now()->addSeconds(3600), function () use ($tenant) {
            return Service::query()
                ->where('idtenant', $tenant->id)
                ->where('is_active', true)
                ->orderByDesc('is_popular')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Service $service) => $service->getAttributes())
                ->all();
        });

        $services = Service::hydrate($servicesData);
        $services->load('category');
        $services = $services->filter(fn (Service $service) => $service->hasActiveFulfillment());

        $servicesPayload = $services->map(function (Service $service) {
            $priceLabel = 'Rp '.number_format($service->harga, 0, ',', '.');

            return [
                'id' => $service->id,
                'category_id' => $service->idcategory ? (int) $service->idcategory : null,
                'category_name' => $service->category?->name,
                'name' => $service->namalayanan,
                'price' => (float) $service->harga,
                'price_label' => $priceLabel,
                'price_unit' => $service->satuan_harga ?: 'sesi',
                'duration' => (int) ($service->durasi ?? 60),
                'duration_unit' => $service->satuan_durasi ?: 'menit',
            ];
        })->values();

        $categories = $services->pluck('category')->filter(fn ($cat) => $cat && $cat->is_active)->unique('id')->values();
        $pendingCheckout = app(StorefrontCheckoutState::class)->pending($tenant);

        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
        $acceptsOnlineBookings = EntitlementRules::hasEffectivePlan($subscription);

        return view('customer.booking.program-selection', compact('tenant', 'services', 'servicesPayload', 'categories', 'pendingCheckout', 'acceptsOnlineBookings'));
    }

    public function selectProgram(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        if (! $tenant) {
            abort(404);
        }

        $validated = $request->validate([
            'service_id' => ['required', 'integer'],
        ]);

        $service = $this->resolveService($tenant->id, (int) $validated['service_id']);

        if (! $service || ! $service->hasActiveFulfillment()) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha)
                ->withErrors(['service' => 'Layanan ini sedang tidak tersedia karena staf atau sumber daya tidak aktif.']);
        }

        $this->drafts->start($tenant->id, $service->id);

        return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha);
    }
}
