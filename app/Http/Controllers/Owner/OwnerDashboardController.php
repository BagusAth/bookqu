<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Dashboard\GetBeranda;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OwnerDashboardController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * Beranda (owner-portal.md §8.1).
     */
    public function index(GetBeranda $getBeranda): View
    {
        $user = auth()->user() ?? abort(403, 'User tidak ditemukan.');
        $tenant = $this->resolveTenant();

        if ($tenant === null) {
            // An account without a business yet: only the guide's first step applies.
            return view('owner.dashboard', [
                'tenant' => (new Tenant)->setRelation('user', $user),
                'banner' => null,
                'guide' => ['steps' => [['key' => 'profile', 'label' => 'Lengkapi profil bisnis', 'hint' => 'Nama bisnis, jenis bisnis, nomor HP dan alamat.', 'done' => false, 'event' => 'open-complete-profile', 'url' => null]], 'done' => 0],
                'live' => [],
                'link' => null,
                'quickActions' => [],
                'profileIncomplete' => true,
            ]);
        }

        $page = $getBeranda->execute($tenant, $user);
        $profileStep = collect($page['guide']['steps'] ?? [])->firstWhere('key', 'profile');

        return view('owner.dashboard', $page + [
            'tenant' => $tenant,
            'profileIncomplete' => $profileStep !== null && ! $profileStep['done'],
        ]);
    }

    /**
     * Live refresh of sections 2–4 (AC-BRD-06); amounts are left out for roles without revenue.
     */
    public function pollingData(GetBeranda $getBeranda): JsonResponse
    {
        $tenant = $this->resolveTenant();
        if ($tenant === null) {
            return response()->json(['success' => false, 'message' => 'Tenant tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'data' => $getBeranda->live($tenant, auth()->user() ?? abort(403))]);
    }

    /**
     * The booking link was copied or shared from the link card (guide step 4).
     */
    public function linkShared(GetBeranda $getBeranda): Response
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');
        $getBeranda->markLinkShared($tenant);

        return response()->noContent();
    }
}
