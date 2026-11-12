<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\Media\DeleteMediaAsset;
use App\Actions\Media\StoreMediaAsset;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\PlanCapability;
use App\Domain\Tenant\CustomDomain;
use App\Models\Asset;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantLandingPage;
use App\Support\StorefrontBranding;
use App\Support\TenantContext;

/**
 * The Halaman Booking page (owner-portal.md §8.9): Link & QR, Materi promosi, Tampilan and Galeri
 * media, and for Pro the Landing page and Domain sendiri (BQ-154); below Pro those two show the upgrade prompt.
 */
class GetBookingPageData
{
    public const SECTIONS = ['link' => 'Link & QR', 'promosi' => 'Materi promosi', 'tampilan' => 'Tampilan', 'galeri' => 'Galeri media', 'landing' => 'Landing page', 'domain' => 'Domain sendiri'];

    public function __construct(
        private BuildBookingQr $buildBookingQr,
        private DeleteMediaAsset $deleteMediaAsset,
        private CheckCustomDomain $checkCustomDomain,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Tenant $tenant, ?string $section): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $link = $this->buildBookingQr->link($tenant);
        $name = (string) ($tenant->namabisnis ?: 'kami');
        $assets = Asset::where('idtenant', $tenant->id)->latest()->latest('id')->get();
        $branding = StorefrontBranding::for($tenant);
        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
        $isPro = EntitlementRules::canAccessFeature($subscription, PlanCapability::FEATURE_LANDING_PAGE)['allowed'];

        return [
            'activeSection' => array_key_exists((string) $section, self::SECTIONS) ? (string) $section : 'link',
            'link' => $link,
            'whatsappShareUrl' => 'https://wa.me/?text='.rawurlencode('Booking '.$name.' online di sini: '.$link),
            'qrSvg' => $this->buildBookingQr->svg($tenant),
            'materials' => [
                'instagram' => $name.' · booking online 24 jam'."\n".$link,
                'whatsapp' => 'Halo! Sekarang booking '.$name.' bisa langsung online: pilih layanan, pilih jam, lalu bayar. Klik di sini: '.$link,
            ],
            'appearance' => [
                'theme_color' => $branding['color'],
                'button_style' => $branding['buttonStyle'],
                'card_style' => $branding['cardStyle'],
                'font_family' => $branding['font'],
                'deskripsi' => (string) ($tenant->deskripsi ?? ''),
                'logo_asset_id' => $assets->firstWhere('file_path', $tenant->logo_path)?->id,
                'cover_asset_id' => $assets->firstWhere('file_path', $tenant->banner_path)?->id,
            ],
            'pickerImages' => $assets->map(fn (Asset $asset): array => ['id' => (int) $asset->id, 'url' => $asset->url, 'alt' => (string) $asset->title])->values()->all(),
            'gallery' => $assets->map(fn (Asset $asset): array => [
                'id' => (int) $asset->id,
                'url' => $asset->url,
                'title' => (string) $asset->title,
                'category' => StoreMediaAsset::CATEGORIES[$asset->category] ?? 'Lainnya',
                'size' => $asset->file_size ? number_format($asset->file_size / 1024, 0, ',', '.').' KB' : null,
                'usage' => $this->deleteMediaAsset->usage($tenant, $asset),
            ])->values()->all(),
            'branding' => [
                'colors' => ['#4F46E5', '#382186', '#0F766E', '#B45309', '#BE123C', '#0369A1', '#111827'],
                'buttons' => StorefrontBranding::BUTTON_STYLES,
                'cards' => StorefrontBranding::CARD_STYLES,
                'fonts' => StorefrontBranding::FONTS,
            ],
            'categories' => StoreMediaAsset::CATEGORIES,
            'isPro' => $isPro,
            'landing' => $this->landing($tenant),
            'domain' => $this->domain($tenant),
        ];
    }

    /**
     * The saved landing page form values (BQ-154).
     *
     * @return array{template: string, headline: string, sections: list<string>, banner_asset_id: ?int, published: bool, templates: array<string, string>, sectionLabels: array<string, string>}
     */
    private function landing(Tenant $tenant): array
    {
        $landing = TenantLandingPage::query()->where('idtenant', $tenant->id)->first();

        return [
            'template' => $landing->template ?? 'modern',
            'headline' => (string) ($landing->headline ?? ''),
            'sections' => $landing->sections ?? array_keys(TenantLandingPage::SECTIONS),
            'banner_asset_id' => $landing?->banner_asset_id,
            'published' => $landing?->published_at !== null,
            'templates' => TenantLandingPage::TEMPLATES,
            'sectionLabels' => TenantLandingPage::SECTIONS,
        ];
    }

    /**
     * The own domain, its last check and the DNS instructions (BQ-154, AC-HBK-04).
     *
     * @return array{name: string, status: ?string, statusLabel: ?string, checkedAt: ?string, cnameTarget: string, addresses: list<string>, configured: bool}
     */
    private function domain(Tenant $tenant): array
    {
        $status = $tenant->custom_domain ? (string) ($tenant->custom_domain_status ?? CustomDomain::STATUS_PENDING) : null;

        return [
            'name' => (string) ($tenant->custom_domain ?? ''),
            'status' => $status,
            'statusLabel' => match ($status) {
                CustomDomain::STATUS_CONNECTED => 'Terhubung',
                CustomDomain::STATUS_NOT_CONNECTED => 'Belum terhubung',
                CustomDomain::STATUS_PENDING => 'Menunggu pengecekan',
                default => null,
            },
            'checkedAt' => $tenant->custom_domain_checked_at?->timezone('Asia/Jakarta')->translatedFormat('j M Y, H:i'),
            'cnameTarget' => $this->checkCustomDomain->target(),
            'addresses' => $this->checkCustomDomain->addresses(),
            'configured' => $this->checkCustomDomain->configured(),
        ];
    }
}
