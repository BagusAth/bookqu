<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Media\DeleteMediaAsset;
use App\Actions\Media\StoreMediaAsset;
use App\Actions\Tenant\BuildBookingQr;
use App\Actions\Tenant\GetBookingPageData;
use App\Actions\Tenant\UpdateAppearanceSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreMediaAssetRequest;
use App\Http\Requests\Tenant\UpdateAppearanceRequest;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman Booking (BQ-142, owner-portal.md §8.9). Pemilik and Manajer through `booking-page.manage`;
 * behind the `booking-page` module until it is released (ADR-0021).
 */
class OwnerBookingPageController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request, GetBookingPageData $getBookingPageData): View
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

        return view('owner.booking-page', $getBookingPageData->execute(
            $tenant,
            is_string($request->query('section')) ? $request->query('section') : null,
        ) + ['tenant' => $tenant]);
    }

    public function updateAppearance(UpdateAppearanceRequest $request, UpdateAppearanceSettings $updateAppearanceSettings): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $updateAppearanceSettings->execute($tenant, $request->validated());

        return redirect()->route('owner.booking-page', ['section' => 'tampilan'])->with('sukses', 'Tampilan halaman booking disimpan.');
    }

    /**
     * The QR code of the current booking link, as an SVG download (AC-HBK-02).
     */
    public function qr(BuildBookingQr $buildBookingQr): StreamedResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $svg = $buildBookingQr->svg($tenant);

        return response()->streamDownload(fn () => print ($svg), 'qr-'.Str::slug((string) $tenant->slug).'.svg', ['Content-Type' => 'image/svg+xml']);
    }

    /**
     * A printable poster with the QR code and the business name (FR-PUBLIC-006).
     */
    public function poster(BuildBookingQr $buildBookingQr): View
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

        return view('owner.booking-page-poster', [
            'tenant' => $tenant,
            'link' => $buildBookingQr->link($tenant),
            'qrSvg' => $buildBookingQr->svg($tenant),
        ]);
    }

    public function storeMedia(StoreMediaAssetRequest $request, StoreMediaAsset $storeMediaAsset): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $validated = $request->validated();
        $asset = $storeMediaAsset->execute($tenant, $request->file('file'), $validated['title'] ?? null, $validated['category'] ?? null);

        return redirect()->route('owner.booking-page', ['section' => 'galeri'])->with('sukses', 'Gambar "'.$asset->title.'" ditambahkan ke galeri.');
    }

    public function destroyMedia(Request $request, int $asset, DeleteMediaAsset $deleteMediaAsset): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $title = $deleteMediaAsset->execute($tenant, $asset, $request->boolean('confirm'));

        return redirect()->route('owner.booking-page', ['section' => 'galeri'])->with('sukses', 'Gambar "'.$title.'" dihapus.');
    }

    /**
     * The former appearance and assets pages moved here (BQ-142 plan point 3).
     */
    public function legacyAppearance(): RedirectResponse
    {
        return redirect()->route('owner.booking-page', ['section' => 'tampilan'], 301);
    }

    public function legacyAssets(): RedirectResponse
    {
        return redirect()->route('owner.booking-page', ['section' => 'galeri'], 301);
    }
}
