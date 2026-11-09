<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Owner\GetPaymentsPage;
use App\Actions\Owner\RequestOwnerPayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\RequestPayoutRequest;
use App\Models\OwnerPayout;
use App\Models\TenantPayoutAccount;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerBalanceController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * The Pembayaran page (owner-portal.md §8.12). Pemilik only through `payouts.manage` (AC-PAY-01).
     */
    public function index(Request $request, GetPaymentsPage $getPaymentsPage): View
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

        return view('owner.payments', $getPaymentsPage->execute(
            $tenant,
            $request->user() ?? abort(403),
            is_string($request->query('tab')) ? $request->query('tab') : null,
        ) + ['tenant' => $tenant]);
    }

    /**
     * The former balance page now is the Saldo & Pencairan tab (§8.12).
     */
    public function legacyBalance(Request $request): RedirectResponse
    {
        return redirect()->route('owner.payments', ['tab' => 'saldo'] + $request->query(), 301);
    }

    public function store(RequestPayoutRequest $request, RequestOwnerPayout $requestOwnerPayout): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404, 'Tenant tidak ditemukan.');

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

        return redirect()->route('owner.payments', ['tab' => 'saldo'])->with('sukses', 'Permintaan pencairan dibuat dan menunggu review admin.');
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
