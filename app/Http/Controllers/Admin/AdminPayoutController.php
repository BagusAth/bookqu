<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ProcessOwnerPayout;
use App\Domain\Payout\PayoutState;
use App\Http\Controllers\Controller;
use App\Models\OwnerPayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPayoutController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        // scope-bypass: platform admin (role:admin route) lists payouts of every tenant
        $payouts = OwnerPayout::withoutGlobalScopes()
            ->with(['tenant:id,namabisnis,iduser,saldo_platform', 'tenant.user:id,email', 'payoutAccount'])
            ->when(is_string($status) && $status !== '', fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status IN ('pending', 'approved') THEN 0 ELSE 1 END")
            ->orderByDesc('requested_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.payouts', ['payouts' => $payouts, 'status' => $status]);
    }

    public function approve(Request $request, int $payout, ProcessOwnerPayout $process): RedirectResponse
    {
        return $this->respond($process->execute($payout, PayoutState::APPROVED, $request->user()));
    }

    public function complete(Request $request, int $payout, ProcessOwnerPayout $process): RedirectResponse
    {
        $data = $request->validate([
            'transfer_reference' => ['nullable', 'string', 'max:150', 'required_without:proof'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120', 'required_without:transfer_reference'],
        ], [
            'transfer_reference.required_without' => 'Isi referensi transfer atau unggah bukti transfer.',
            'proof.required_without' => 'Isi referensi transfer atau unggah bukti transfer.',
        ]);

        return $this->respond($process->execute($payout, PayoutState::SUCCESS, $request->user(), $data, $request->file('proof')));
    }

    public function reject(Request $request, int $payout, ProcessOwnerPayout $process): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        return $this->respond($process->execute($payout, PayoutState::REJECTED, $request->user(), $data));
    }

    public function fail(Request $request, int $payout, ProcessOwnerPayout $process): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        return $this->respond($process->execute($payout, PayoutState::FAILED, $request->user(), $data));
    }

    private function respond(bool $changed): RedirectResponse
    {
        return $changed
            ? back()->with('sukses', 'Status withdraw diperbarui.')
            : back()->withErrors(['payout' => 'Status withdraw tidak dapat diubah dari kondisi saat ini.']);
    }
}
