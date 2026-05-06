<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class BookingController extends Controller
{
    public function showProgramSelection(string $slug_usaha)
    {
        $tenantData = Cache::remember("tenant:slug:{$slug_usaha}", now()->addHour(), function () use ($slug_usaha) {
            return Tenant::query()->where('slug', $slug_usaha)->first()?->getAttributes();
        });

        if (!$tenantData) {
            abort(404);
        }

        $tenant = Tenant::hydrate([$tenantData])->first();

        $servicesData = Cache::remember("tenant:{$tenant->id}:services:active", now()->addHour(), function () use ($tenant) {
            return Service::query()
                ->where('idtenant', $tenant->id)
                ->where('is_active', true)
                ->orderByDesc('is_popular')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Service $service) => $service->getAttributes())
                ->all();
        });

        $services = Service::hydrate($servicesData ?? []);

        $servicesPayload = $services->map(function (Service $service) {
            $priceLabel = 'Rp ' . number_format($service->harga, 0, ',', '.');

            return [
                'id' => $service->id,
                'name' => $service->namalayanan,
                'price' => (float) $service->harga,
                'price_label' => $priceLabel,
                'price_unit' => $service->satuan_harga ?: 'sesi',
            ];
        })->values();

        return view('customer.booking.program-selection', compact('tenant', 'services', 'servicesPayload'));
    }
}
