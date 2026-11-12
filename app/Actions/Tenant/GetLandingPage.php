<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\PlanCapability;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantLandingPage;

/**
 * The landing page as the storefront shows it (BQ-154, FR-PUBLIC-007): template, headline, banner
 * from the media gallery and the chosen sections, filled from the business's own data. The storefront
 * shows it only while it is published and the business is on Pro; the owner preview shows the draft.
 */
class GetLandingPage
{
    private const HIGHLIGHTED_SERVICES = 3;

    /**
     * The published landing page for the storefront home, or null.
     */
    public function published(Tenant $tenant, ?Subscription $subscription): ?array
    {
        if (! EntitlementRules::canAccessFeature($subscription, PlanCapability::FEATURE_LANDING_PAGE)['allowed']) {
            return null;
        }

        $landing = TenantLandingPage::query()->where('idtenant', $tenant->id)->whereNotNull('published_at')->with('banner')->first();

        return $landing instanceof TenantLandingPage ? $this->present($tenant, $landing) : null;
    }

    /**
     * The saved draft for the owner preview, or null when nothing is saved yet.
     */
    public function draft(Tenant $tenant): ?array
    {
        $landing = TenantLandingPage::query()->where('idtenant', $tenant->id)->with('banner')->first();

        return $landing instanceof TenantLandingPage ? $this->present($tenant, $landing) : null;
    }

    /**
     * @return array{template: string, headline: string, bannerUrl: ?string, sections: array<string, mixed>}
     */
    private function present(Tenant $tenant, TenantLandingPage $landing): array
    {
        $sections = [];
        foreach ($landing->sections ?? [] as $section) {
            $content = match ($section) {
                'tentang' => trim((string) $tenant->deskripsi) !== '' ? trim((string) $tenant->deskripsi) : null,
                'layanan' => $this->services($tenant),
                'kontak' => $this->contact($tenant),
                default => null,
            };
            if ($content !== null && $content !== []) {
                $sections[$section] = $content;
            }
        }

        return [
            'template' => array_key_exists($landing->template, TenantLandingPage::TEMPLATES) ? $landing->template : 'modern',
            'headline' => $landing->headline ?? (string) $tenant->namabisnis,
            'bannerUrl' => $landing->banner?->url ?: null,
            'sections' => $sections,
        ];
    }

    /**
     * @return list<array{name: string, price: string}>
     */
    private function services(Tenant $tenant): array
    {
        return Service::query()
            ->where('idtenant', $tenant->id)
            ->where('is_active', true)
            ->orderByDesc('is_popular')
            ->orderBy('namalayanan')
            ->limit(self::HIGHLIGHTED_SERVICES)
            ->get(['namalayanan', 'harga'])
            ->map(fn (Service $service): array => [
                'name' => (string) $service->namalayanan,
                'price' => 'Rp '.number_format((float) $service->harga, 0, ',', '.'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{address: string, phone: string, whatsappUrl: ?string}|null
     */
    private function contact(Tenant $tenant): ?array
    {
        $address = trim((string) $tenant->alamat);
        $phone = trim((string) $tenant->nomorhp);
        if ($address === '' && $phone === '') {
            return null;
        }

        $digits = (string) preg_replace('/\D+/', '', $phone);
        $international = str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;

        return [
            'address' => $address,
            'phone' => $phone,
            'whatsappUrl' => $international !== '' ? 'https://wa.me/'.$international : null,
        ];
    }
}
