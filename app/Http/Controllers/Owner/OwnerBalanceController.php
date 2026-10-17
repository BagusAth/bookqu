<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerPayout;
use App\Models\Payment;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\View\View;

class OwnerBalanceController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(): View
    {
        $tenant = $this->resolveTenant();
        $availableBalance = $tenant?->saldo_platform ?? 0;

        $pendingSettlement = Payment::where('idtenant', $tenant?->id)
            ->where('status', 'pending')
            ->where('tipe', 'booking')
            ->sum('jumlah');

        $totalEarnings = Payment::where('idtenant', $tenant?->id)
            ->where('status', 'sukses')
            ->where('tipe', 'booking')
            ->sum('jumlah');

        $payouts = OwnerPayout::where('idtenant', $tenant?->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('owner.balance', compact('tenant', 'availableBalance', 'pendingSettlement', 'totalEarnings', 'payouts'));
    }
}
