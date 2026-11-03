<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerPayout;
use App\Models\Payment;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->latest('requested_at')
            ->paginate(15)
            ->withQueryString();

        $requestKey = (string) Str::uuid();

        return view('owner.balance', compact('tenant', 'availableBalance', 'pendingSettlement', 'totalEarnings', 'payouts', 'requestKey'));
    }

    /**
     * Transfer proof of the owner's own payout (private disk; tenant-scoped lookup).
     */
    public function proof(int $payout): StreamedResponse
    {
        $tenant = $this->resolveTenant();
        $record = OwnerPayout::where('idtenant', $tenant?->id)->whereKey($payout)->first();

        abort_if($record === null || ! $record->proof_path || ! Storage::disk('local')->exists($record->proof_path), 404);

        return Storage::disk('local')->download($record->proof_path);
    }
}
