<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\Media\DeleteMediaAsset;
use App\Actions\Media\StoreMediaAsset;
use App\Models\Asset;
use App\Models\Tenant;
use App\Support\StorefrontBranding;
use App\Support\TenantContext;

/**
 * The Halaman Booking page (owner-portal.md §8.9): Link & QR, Materi promosi, Tampilan and Galeri
 * media. Landing page and own domain (Pro) are separate items.
 */
class GetBookingPageData
{
    public const SECTIONS = ['link' => 'Link & QR', 'promosi' => 'Materi promosi', 'tampilan' => 'Tampilan', 'galeri' => 'Galeri media'];

    public function __construct(
        private BuildBookingQr $buildBookingQr,
        private DeleteMediaAsset $deleteMediaAsset,
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
        ];
    }
}
