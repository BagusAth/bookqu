<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Owner\RequestOwnerPayout;
use App\Domain\Payout\PayoutEligibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\RequestPayoutRequest;
use App\Models\OwnerPayout;
use App\Models\Payment;
use App\Models\TenantPayoutAccount;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerBalanceController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request): View
    {
        $tenant = $this->resolveTenant();
        $availableBalance = $tenant?->saldo_platform ?? 0;

        $inFlightPayouts = OwnerPayout::where('idtenant', $tenant?->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('jumlah');

        $pendingSettlement = $inFlightPayouts;

        $totalEarnings = Payment::where('idtenant', $tenant?->id)
            ->where('status', 'sukses')
            ->where('tipe', 'booking')
            ->sum('jumlah');

        $payouts = OwnerPayout::where('idtenant', $tenant?->id)
            ->latest('requested_at')
            ->paginate(15)
            ->withQueryString();

        $payoutAccounts = TenantPayoutAccount::where('idtenant', $tenant?->id)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        $requestKey = (string) Str::uuid();

        $eligibilityReasons = $tenant ? PayoutEligibility::reasons($tenant, $request->user()) : [];
        $isEligible = empty($eligibilityReasons);

        $minPayoutAmount = (int) config('payouts.min_amount', 10000);

        return view('owner.balance', compact(
            'minPayoutAmount',
            'tenant',
            'availableBalance',
            'inFlightPayouts',
            'pendingSettlement',
            'totalEarnings',
            'payouts',
            'payoutAccounts',
            'requestKey',
            'eligibilityReasons',
            'isEligible'
        ));
    }

    public function store(RequestPayoutRequest $request, RequestOwnerPayout $requestOwnerPayout): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $account = TenantPayoutAccount::where('idtenant', $tenant->id)
            ->whereNull('deleted_at')
            ->findOrFail((int) $request->input('payout_account_id'));

        $requestOwnerPayout->execute(
            $tenant,
            (int) $request->input('jumlah'),
            $account,
            (string) $request->input('request_key'),
            $request->user()
        );

        return redirect()->route('owner.settings.balance')->with('sukses', 'Permintaan withdraw berhasil dibuat dan menunggu review admin.');
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
