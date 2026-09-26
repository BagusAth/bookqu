<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerPayout;
use App\Models\Payment;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\View\View;

class OwnerPaymentSettingsController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(): View
    {
        $tenant = $this->resolveTenant();
        $payouts = OwnerPayout::where('idtenant', $tenant?->id)->orderByDesc('created_at')->limit(10)->get();
        $transactions = Payment::where('idtenant', $tenant?->id)->with('booking.layanan')->orderByDesc('created_at')->limit(15)->get();

        return view('owner.payment-settings', compact('tenant', 'payouts', 'transactions'));
    }
}
