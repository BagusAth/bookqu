<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Dashboard\GetOwnerDashboardOverview;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Traits\ResolvesOwnerTenant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OwnerDashboardController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(GetOwnerDashboardOverview $dashboardOverview): View
    {
        $tenant = $this->resolveTenant();
        $user = auth()->user();

        if (!$user) {
            abort(403, 'User tidak ditemukan.');
        }

        if (!$tenant) {
            $tenant = new Tenant();
            $tenant->setRelation('user', $user);

            $labelbulan = [];
            $datarevenueperbulan = [];
            for ($i = 6; $i >= 0; $i--) {
                $labelbulan[] = Carbon::now()->subMonths($i)->startOfMonth()->format('M');
                $datarevenueperbulan[] = 0;
            }

            $labelminggu = [];
            $datarevenueperminggu = [];
            for ($i = 6; $i >= 0; $i--) {
                $labelminggu[] = Carbon::today()->subDays($i)->format('d M');
                $datarevenueperminggu[] = 0;
            }

            return view('owner.dashboard', [
                'tenant' => $tenant,
                'totalbooking' => 0,
                'persenperubahanboking' => 0,
                'totalrevenue' => 0,
                'persenperubahanrevenue' => 0,
                'programaktif' => 0,
                'totalpelanggan' => 0,
                'datarevenueperbulan' => $datarevenueperbulan,
                'labelbulan' => $labelbulan,
                'datarevenueperminggu' => $datarevenueperminggu,
                'labelminggu' => $labelminggu,
                'trendlayanan' => collect(),
                'aktivitasterbaru' => collect(),
                'upcomingbookings' => collect(),
                'statustrial' => false,
                'sisahari' => 0,
                'showProfilePrompt' => true,
                'showPaymentPrompt' => false,
            ]);
        }

        $overviewData = $dashboardOverview->getOverviewData($tenant);

        return view('owner.dashboard', $overviewData);
    }

    public function pollingData(GetOwnerDashboardOverview $dashboardOverview): JsonResponse
    {
        $tenant = $this->resolveTenant();

        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant tidak ditemukan.'], 404);
        }

        $data = $dashboardOverview->getPollingData($tenant);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
