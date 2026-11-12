<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Media\DeleteMediaAsset;
use App\Actions\Media\StoreMediaAsset;
use App\Actions\Tenant\BuildBookingQr;
use App\Actions\Tenant\CheckCustomDomain;
use App\Actions\Tenant\GetBookingPageData;
use App\Actions\Tenant\GetLandingPage;
use App\Actions\Tenant\SaveLandingPage;
use App\Actions\Tenant\UpdateAppearanceSettings;
use App\Actions\Tenant\UpdateCustomDomain;
use App\Domain\Tenant\CustomDomain;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SaveLandingPageRequest;
use App\Http\Requests\Tenant\StoreMediaAssetRequest;
use App\Http\Requests\Tenant\UpdateAppearanceRequest;
use App\Http\Requests\Tenant\UpdateCustomDomainRequest;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman Booking (BQ-142, owner-portal.md §8.9). Pemilik and Manajer through `booking-page.manage`;
 * behind the `booking-page` module until it is released (ADR-0021). Landing page and own domain
 * (BQ-154) need Pro, enforced by `subscription:landing-page` on their routes (AC-HBK-03).
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
     * Landing page (BQ-154, FR-PUBLIC-007): save the draft, publish it on the storefront home, or hide it.
     */
    public function saveLanding(SaveLandingPageRequest $request, SaveLandingPage $saveLandingPage): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $action = (string) $request->validated('action');
        $saveLandingPage->execute($tenant, $request->safe()->except('action'), $action);

        return redirect()->route('owner.booking-page', ['section' => 'landing'])->with('sukses', match ($action) {
            'publish' => 'Landing page diterbitkan di halaman booking Anda.',
            'unpublish' => 'Landing page disembunyikan. Halaman booking kembali ke tampilan biasa.',
            default => 'Landing page disimpan.',
        });
    }

    /**
     * The saved landing page as customers would see it, before publishing.
     */
    public function previewLanding(GetLandingPage $getLandingPage): View
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

        return view('owner.booking-page-landing-preview', ['tenant' => $tenant, 'landing' => $getLandingPage->draft($tenant)]);
    }

    /**
     * Domain sendiri (BQ-154, D-005): save or remove the domain; a new domain is checked right away.
     */
    public function updateDomain(UpdateCustomDomainRequest $request, UpdateCustomDomain $updateCustomDomain): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $domain = $request->validated('custom_domain');
        $updateCustomDomain->execute($tenant, is_string($domain) ? $domain : null);

        return redirect()->route('owner.booking-page', ['section' => 'domain'])->with('sukses', is_string($domain)
            ? 'Domain '.$domain.' disimpan. Arahkan DNS-nya sesuai petunjuk, lalu cek koneksi.'
            : 'Domain sendiri dihapus. Pelanggan memakai link BookQu Anda.');
    }

    /**
     * "Cek sekarang": the DNS check runs at once and reports what the domain points to (AC-HBK-04).
     */
    public function checkDomain(CheckCustomDomain $checkCustomDomain): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $redirect = redirect()->route('owner.booking-page', ['section' => 'domain']);
        if ((string) $tenant->custom_domain === '') {
            return $redirect->with('error', 'Simpan domain terlebih dahulu.');
        }

        $result = $checkCustomDomain->execute($tenant);
        $found = [...$result['cnames'], ...$result['ipv4']];
        $redirect->with('domain_dns', $found);

        if ($result['status'] === CustomDomain::STATUS_CONNECTED) {
            return $redirect->with('sukses', 'Domain terhubung. Pelanggan sudah bisa membuka halaman booking lewat '.$tenant->custom_domain.'.');
        }

        return $redirect->with('error', $result['configured']
            ? 'Domain belum terhubung. DNS saat ini: '.($found !== [] ? implode(', ', $found) : 'tidak ada catatan CNAME atau A').'.'
            : 'Domain belum bisa dicek karena tujuan DNS BookQu belum diatur. Hubungi tim BookQu.');
    }

    /**
     * The former landing page builder is now a section of Halaman Booking (owner-portal.md §10.2).
     */
    public function legacyLanding(): RedirectResponse
    {
        return redirect()->route('owner.booking-page', ['section' => 'landing'], 301);
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
